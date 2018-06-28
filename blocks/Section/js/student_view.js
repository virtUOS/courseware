import 'jquery.scrollto'

import Backbone from 'backbone'
import $ from 'jquery'
import _ from 'underscore'
import StudentView from 'js/student_view'
import BlockModel from 'js/block_model'
import block_types from 'js/block_types'
import helper from 'js/url'
import i18n from 'js/i18n'
import templates from 'js/templates'
import EditView from './edit_view'
import tooltip from 'js/tooltip'

function findBlockForEvent(event) {
  return $(event.target).closest('.block');
}

function findBlockIDForEvent(event) {
  return parseInt(findBlockForEvent(event).attr('data-blockid'), 10);
}

function getBlockPositions($el) {
  return $el.find('.block').map(function (i, el) {
    return parseInt($(el).attr('data-blockid'), 10);
  }).toArray();
}

export default StudentView.extend({

  events: {
    'click .title .edit':     'editSection',
    'click .title .trash':    'destroySection',

    'click .cw-block-adder-selector': 'showBlockAdderTab',
    'click .cw-block-adder-header': 'toggleBlockAdder',
    'click .cw-block-adder-item': 'addNewBlock',

    // child block stuff

    'click .block .lower':    'lowerBlock',
    'click .block .raise':    'raiseBlock',

    'click .block .author':   'switchToAuthorView',
    'click .block .trash':    'destroyView'
  },

  initialize() {
    this.children = {};

    _.each(this.$('section.block'), function (element) {
      var block = this.initializeBlock(element, undefined, 'student');
      block.initializeFromDOM();
    }, this);

    this.listenTo(Backbone, 'modeswitch', this.switchMode, this);
  },

  remove() {
    StudentView.prototype.remove.call(this);
    _.invoke(this.children, 'remove');
  },

  render() {
    return this;
  },

  postRender() {
    _.each(this.children, function (block) {
      if (typeof block.postRender === 'function') {
        block.postRender();
      }
    });
    tooltip(this.$el, 'button.edit,button.trash');
    this.$('.cw-block-adder-tab').not('.cw-block-adder-tab[data-blockclass="all"]').hide();
    this.$('.cw-block-adder-selector[data-blockclass="all"]').addClass('cw-active-selector');
  },

  switchMode(view) {
    if (view === 'student') {
      _.each(this.children, function (child, child_id) {
        this.switchView(child_id, view);
      }, this);
    }
  },

  switchToAuthorView(event) {
    var id = findBlockIDForEvent(event);
    this.switchView(id, 'author');
  },

  destroyView(event) {
    var block_id = findBlockIDForEvent(event),
        block_view = this.children[block_id],
        $block_wrapper = block_view.$el.closest('section.block'),
        self = this;

    if (confirm(i18n('Wollen Sie den Block wirklich löschen?'))) {

      $block_wrapper.addClass('loading');

      helper.callHandler(this.model.id, 'remove_content_block', { child_id: block_id })
        .then(
          function () {
            block_view.remove();
            delete self.children[block_id];
            $block_wrapper.remove();
            self.refreshBlockTypes(self.model.id, self.$('.block-types'));
          },

          function (error) {
            $block_wrapper.removeClass('loading');
            var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
            alert(errorMessage);
            console.log(errorMessage, arguments);
            self.refreshBlockTypes(self.model.id, self.$('.block-types'));
          }
        )
    }
  },

  switchView(block_id, view_name) {
    var block_view = this.children[block_id],
        model = block_view.model,
        $block_wrapper = block_view.$el.closest('section.block');

    // already switched
    if (block_view.view_name === view_name) {
      return;
    }

    // TODO: switch on view_name!!
    $block_wrapper.find('.controls button.author').toggle();

    block_view.remove();

    // create new view
    var el = $('<div class="block-content"/>').attr('data-view', view_name);
    $block_wrapper.append(el).addClass('loading');

    var view = block_types
        .findByName(model.get('type'))
        .createView(view_name, {
          el: el,
          model: model
        });

    this.addBlock(view);

    view.renderServerSide().then(function () {
      $block_wrapper.removeClass('loading');
    });
  },
  
   toggleBlockAdder() {
        var $view = this,
            $header = $view.$('.cw-block-adder-header'),
            $wrapper = $view.$('.cw-block-adder-wrapper');
        if ($header.hasClass('cw-block-adder-open')) {
                $wrapper.hide();
                $header.removeClass('cw-block-adder-open');
        } else {
            $wrapper.show();
            $header.addClass('cw-block-adder-open');
        }
    },
    
    showBlockAdderTab(event) {
        var $view = this,
            $button = $(event.target),
            $block_class = $button.attr('data-blockclass');
        $view.$('.cw-block-adder-tab').hide();
        $view.$('.cw-block-adder-selector').removeClass('cw-active-selector');
        $button.addClass('cw-active-selector');
        $view.$('.cw-block-adder-tab[data-blockclass="'+$block_class+'"]').show();
    },

  addNewBlock(event) {
    var view = this,
        $item = $(event.currentTarget),
        block_type = $item.attr('data-blocktype'),
        block_sub_type = $item.attr('data-blocksubtype');

    helper
      .callHandler(this.model.id, 'add_content_block', { type: block_type, sub_type: block_sub_type })

      .then(function (data) {
        var model = new BlockModel(data),
            view_name = model.get('editable') ? 'author' : 'student',
            block_stub = view.appendBlockStub(model, view_name),
            $el = block_stub.$el.closest('section.block'),
            block_name = $item.attr('data-blockname');

        $el.addClass('loading');
        block_stub.renderServerSide().then(function () {
          $el.removeClass('loading');
          // hide the edit button when the form is shown
          $el.find('.controls button.author').hide();
          //insert block name
          $el.find('.controls span.type').html(block_name);
        });
        view.toggleBlockAdder();
        $('html, body').animate({
            scrollTop: $el.offset().top
        }, 2000);
      })
      .catch(function (error) {
        var errorMessage = 'Could not add the block: ' + $.parseJSON(error.responseText).reason;
        alert(errorMessage);
        console.log(errorMessage, arguments);
        view.toggleBlockAdder();
      });
  },

  appendBlockStub(model, view_name) {
    var block_wrapper = templates('Section', 'block_wrapper', model.toJSON()),
        block_el = this.$('.no-content').before(block_wrapper).prev();

    return this.initializeBlock(block_el, model, view_name);
  },

  initializeBlock(block, model, view_name) {
    var $block = $(block),
        $el    = $block.find('div.block-content'),
        view;

    if (!_.isObject(model)) {
      model  = new BlockModel({
        id:   $block.attr('data-blockid'),
        type: $block.attr('data-blocktype')
      });
    }

    view = block_types
      .findByName(model.get('type'))
      .createView(view_name, { el: $el, model });

    return this.addBlock(view);
  },

  addBlock(block) {
    this.children[block.model.id] = block;
    this.listenTo(block, 'switch', _.bind(this.switchView, this, block.model.id));

    return block;
  },

  editSection() {
    var $title = this.$('> .title'),
        view = new EditView({ model: this.model }),
        $wrapped = $title.wrapInner('<div/>').children().first(),
        self = this,
        updateSectionTitle = function (model) {
          var new_title = templates('Section', 'title', model.toJSON());
          $title.replaceWith(new_title);
          return self.$('> .title');
        };

    $wrapped.hide().before(view.el);

    view.focus();

    view.promise()
      .then(function (model) {
        if (model.hasChanged()) {
          $title = updateSectionTitle(model).addClass('loading');
          return model.save();
        }

        return false;
      })
      .then(
        function () {
          $title.removeClass('loading');
          view.remove();
          $wrapped.children().unwrap();
        },
        function (error) {
          $title.removeClass('loading');
          updateSectionTitle(self.model.revert());
          if (error) {
            var errorMessage = 'Could not update the section: ' + $.parseJSON(error.responseText).reason;
            alert(errorMessage);
            console.log(errorMessage, arguments);
          }
          view.remove();
          $wrapped.children().unwrap();
        });
  },

  destroySection() {
    if (confirm(i18n('Wollen Sie den gesamten Abschnitt wirklich löschen?'))) {
      $('#courseware').addClass('loading');

      var parent_id = this.model.get('parent_id');

      this.model.destroy()
        .then(function () {
          if (parent_id) {
            helper.navigateTo(parent_id);
          }
        }).catch(function (error) {
          var errorMessage = 'Could not remove the section: ' + $.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
          $('#courseware').removeClass('loading');
        });
    }
  },

  lowerBlock(event) {
    var protagonist = findBlockForEvent(event),
        antagonist = protagonist.next('.block'),
        self = this;

    // cannot lower last block
    if (!antagonist.length) {
      return;
    }

    var scrollTo = function (to, opts) {
      var deferred = new Promise(resolve => {
        $.scrollTo(to, {
          ...opts,
          onAfter() {
            resolve('ok');
          }
        });
      })

      return deferred;
    };

    var new_positions = getBlockPositions(self.$el);
    var thisid = parseInt(protagonist.attr('data-blockid'));
    var index = new_positions.indexOf(thisid);
    new_positions[index] = new_positions[index + 1];
    new_positions[index + 1] = thisid;

    var courseware_id = $('#courseware').attr('data-blockid');
    var data = { parent: self.model.id, positions: new_positions };
    helper
      .callHandler(courseware_id, 'update_positions', data)
      .then(function () {
        return new Promise((resolve, reject) => {
          $.when(
            protagonist.effect('blind', { direction: 'up' })
          ).then(resolve, reject);
        });
      })
      // blind up completed
      .then(function () {
        return scrollTo(antagonist, { duration: 200, over: 1 });
      })
      .then(function () {
        antagonist.after(protagonist);
        protagonist.toggle('blind');
      });
  },

  raiseBlock(event) {
    var protagonist = findBlockForEvent(event),
        antagonist = protagonist.prev('.block'),
        self = this;

    // cannot raise first block
    if (!antagonist.length) {
      return;
    }

    var scrollTo = function (to, opts) {
      var deferred = new Promise(resolve => {
        $.scrollTo(to, {
          ...opts,
          onAfter() {
            resolve('ok');
          }
        });
      })
      return deferred;
    };

    var new_positions = getBlockPositions(self.$el);
    var thisid = parseInt(protagonist.attr('data-blockid'));
    var index = new_positions.indexOf(thisid);
    new_positions[index] = new_positions[index - 1];
    new_positions[index - 1] = thisid;

    var courseware_id = $('#courseware').attr('data-blockid');
    var data = { parent:    self.model.id, positions: new_positions };
    helper
      .callHandler(courseware_id, 'update_positions', data)
      .then(function () {
        return new Promise((resolve, reject) => {
          $.when(
            protagonist.effect('blind', { direction: 'up' })
          ).then(resolve, reject);
        });
      })
      // blind up completed
      .then(function () {
        return scrollTo(antagonist, { duration: 200, offset: -50 });
      })
      .then(function () {
        antagonist.before(protagonist);
        protagonist.toggle('blind');
      });
  },

  refreshBlockTypes(sectionId, container) {
    var model = { id: sectionId };
    var options = { el: container, model: model };
    var section = block_types.findByName('Section');
    var blockTypesStub = section.createView('block_types', options);
    blockTypesStub.renderServerSide();
  }
});
