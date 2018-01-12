import jQuery from 'jquery'
import Backbone from 'backbone'
import dateformat from 'dateformat'
import helper from 'js/url'
import templates from 'js/templates'
import i18n from 'js/i18n'
import BlockModel from 'js/block_model'

import EditView from './edit_structure'

export default Backbone.View.extend({

  events: {
    'click .add-chapter': 'addStructure',
    'click .add-subchapter': 'addStructure',
    'click .add-section': 'addStructure',

    'click .chapter    > .title .edit': 'editStructure',
    'click .subchapter > .title .edit': 'editStructure',

    'click .chapter    > .title .trash': 'destroyStructure',
    'click .subchapter > .title .trash': 'destroyStructure',

    'click .init-sort-chapter': 'initSorting',
    'click .init-sort-subchapter': 'initSorting',

    'click .stop-sort-chapter': 'stopSorting',
    'click .stop-sort-subchapter': 'stopSorting',

    'click .activate-aside-section': 'activateAsideSection',
    'click .deactivate-aside-section': 'deactivateAsideSection'
  },

  initialize() {
    this.listenTo(Backbone, 'modeswitch', this.stopSorting, this);
  },

  render() {
    return this;
  },

  postRender() {
  },

  addStructure(event) {
    var $button = jQuery(event.target),
        id = $button.closest('[data-blockid]').attr('data-blockid');

    if (id == null) {
      return;
    }

    var model = this._newBlockFromButton($button),
        view = new EditView({ model }),
        insert_point = $button.closest('.controls').prev('.no-content'),
        tag = '<' + insert_point[0].tagName + ' class="addStructure"/>',
        li_wrapper = view.$el.wrap(tag).parent(),
        courseware = this,
        placeholder_item;

    $button.hide();
    insert_point.before(li_wrapper);
    view.postRender();

    view.promise()
      .then(function (model) {
        placeholder_item = insert_point
          .before(templates('Courseware',
                            model.get('type'),
                            model.toJSON()))
          .prev()
          .addClass('loading');

        return courseware._addStructure(id, model);
      })
      .then(function (data) {
        placeholder_item.replaceWith(templates('Courseware', model.get('type'), data));
        view.remove();
        $button.fadeIn();
      }).catch(function (error) {
        placeholder_item && placeholder_item.remove();
        if (error) {
          var errorMessage = 'Could not add the chapter: ' + jQuery.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        }
        view.remove();
        $button.fadeIn();
      });
  },

  _newBlockFromButton($button) {
    var type;

    if ($button.hasClass('add-chapter')) {
      type = 'chapter';
    } else if ($button.hasClass('add-subchapter')) {
      type = 'subchapter';
    } else if($button.hasClass('add-section')) {
      type = 'section';
    }

    var titles = {
      chapter:    i18n('Neues Kapitel'),
      subchapter: i18n('Neues Unterkapitel'),
      section:    i18n('Neuer Abschnitt')
    };

    var visible_since_title = i18n('Sichtbar ab');
    if (type != 'section') {
        return new BlockModel({ title: titles[type], type: type, visible_since_title: visible_since_title });
    } else if (type == 'section') {
        return new BlockModel({ title: titles[type], type: type });
    }
  },

  _addStructure(parent_id, model) {
      
    if (model.attributes.type !='section') {
        var data = {
          parent: parent_id,
          title:  model.get('title'),
          publication_date: model.get('publication_date')
        };
    } else if (model.attributes.type =='section') {
        var data = {
          parent: parent_id,
          title:  model.get('title'),
        };
    } 
    return helper.callHandler(this.model.id, 'add_structure', data);
  },

  editStructure(event) {
    var $parent = jQuery(event.target).closest('[data-blockid]'),
        model = this._modelFromElement($parent),
        $title_el, orig_model, view, updateListItem;

    if (model.isNew()) {
      return;
    }

    if (!model.get('type')) {
      throw 'ERROR';
    }

    $title_el = $parent.find('> .title');

    orig_model = model.clone();

    view = new EditView({ model: model });
    updateListItem = function (model) {

      var title_tmpl = templates('Courseware',
                                 model.get('type').toLowerCase() + '_title',
                                 model.toJSON());

      // update title
      $title_el.replaceWith(title_tmpl);

      // keep this synced
      $parent.data('title', model.get('title'));

      if (model.get('publication_date') != null && !isNaN(model.get('publication_date'))) {
        var date = new Date(model.get('publication_date') * 1000);

        // add class 'unpbulsihed' if publication_date is in the future
        if (new Date().getTime() < date.getTime()) {
          $parent.addClass('unpublished');
        } else {
          $parent.removeClass('unpublished');
        }

        $parent.attr('data-publication', model.get('publication_date'));
      } else {
        $parent.attr('data-publication', '');
      }

      if ($parent.attr('data-publication') != '') {
        $parent.attr('title', 'Freigegeben ab: '+dateformat(new Date($parent.attr('data-publication') * 1000), 'dd.mm.yyyy'));
      } else {
        $parent.attr('title', '');
      }
    };

    $title_el.hide().before(view.el);
    view.postRender();

    view.promise()
      .then(function (model) {
        $parent.addClass('loading');
        if (model.hasChanged()) {
          updateListItem(model);
          return model.save();
        }

        return false;
      })
      .then(function () {
        $parent.removeClass('loading');
        view.remove();
        $title_el.show();
      })
      .catch(function (error) {
        $parent.removeClass('loading');
        updateListItem(orig_model);
        if (error) {
          var errorMessage = 'Could not update the chapter: ' + jQuery.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        }
        view.remove();
        $title_el.show();
      });
  },

  _modelFromElement(element) {
    var values = {
      id: element.data('blockid'),
      title: element.data('title'),
      type: element.data('type'),
      publication_date: parseInt(element.data('publication'), 10)
    };

    return new BlockModel(values);
  },

  destroyStructure(event) {

    var $parent = jQuery(event.target).closest('[data-blockid]'),
        model = this._modelFromElement($parent);

    if (model.isNew()) {
      return;
    }
    if (confirm(i18n('Wollen Sie wirklich löschen? Sämtliche enthaltenen Abschnitte und Blöcke werden unwiderruflich entfernt!'))) {

      $parent.addClass('loading');
      model.destroy({ 
            dataType: "text", 
            success: function(model, response) {
                        console.log("success");
                        if ($parent.hasClass('selected')) {
                          helper.reload();
                        } else {
                          $parent.remove();
                        }
            },
            error: function(model, response) {
                var errorMessage = 'Could not delete the chapter: ' + jQuery.parseJSON(response);
                alert(errorMessage);
                console.log(response);
                $parent.removeClass('loading');
            }
      });
    }
  },

  _sortable: null,
  _original_positions: null,

  _get_positions() {
    return this._sortable.sortable('toArray', { attribute: 'data-blockid' });
  },

  initSorting(event) {
    var element = jQuery(event.target).closest('[data-blockid]'),
        model = this._modelFromElement(element),
        child_types = { Courseware: 'chapter', Chapter: 'subchapter' };

    if (this._sortable) {
      throw 'Already sorting!';
    }

    if (model.get('type') === 'Courseware') {
      this._sortable = this.$el;
    } else {
      this._sortable = this.$('.subchapters');
    }

    this._sortable.sortable({
      items:    '.' + child_types[model.get('type')],
      handle:   '.handle',
      axis:     'y',
      distance: 5,
      opacity:  0.7,
      helper:   function (event, element) {
        return element.clone().find('.subchapters, .controls').remove().end();
      }
    });

    this._original_positions = this._get_positions();
    this.$el.addClass('sorting');
  },

  stopSorting() {

    if (!this._sortable) {
      return;
    }

    var positions = this._get_positions();

    this._sortable.sortable('destroy');

    if (JSON.stringify(positions) !== JSON.stringify(this._original_positions)) {
      const parent_id = this._sortable.closest('[data-blockid]').attr('data-blockid');
      const data = {
        parent:    parent_id,
        positions
      };
      helper.callHandler(this.model.id, 'update_positions', data);
    }

    this._original_positions = this._sortable = null;
    this.$el.removeClass('sorting');
  },

  activateAsideSection(event) {
    var $button = jQuery(event.target),
        block_id = $button.closest('ol').find('.selected').data('blockid');

    $button.prop('disabled', true).addClass('loading');

    helper.callHandler(this.model.id, 'activateAsideSection', { block_id: block_id })
      .then(function () {
        helper.reload();
      }).catch(function (error) {
        $button.prop('disabled', false).removeClass('loading');

        var errorMessage = 'Could not activate the aside section: ' + jQuery.parseJSON(error.responseText).reason;
        alert(errorMessage);
        console.log(errorMessage, arguments);
      });
  },
  
  deactivateAsideSection(event) {
    var $button = jQuery(event.target);
    var model = "", $parent = "";
    var view = this;
    $.each($button.closest('ol.chapters').siblings('.aside-section'), function(index, value){
        if ($(value).data('parenttype') == $button.data('type')) {
            $parent  = $(value);
            model = view._modelFromElement($parent);
        }
    });

    if (model.isNew()) {
      return;
    }
    if (confirm(i18n('Wollen Sie wirklich löschen? Sämtliche enthaltenen Blöcke werden unwiderruflich entfernt!'))) {
        $parent.addClass('loading');
        model.destroy({ 
            dataType: "text", 
            success: function(model, response) {
                        console.log("success");
                        helper.reload();
            },
            error: function(model, response) {
                var errorMessage = 'Could not delete the chapter: ' + jQuery.parseJSON(response);
                alert(errorMessage);
                console.log(response);
                $parent.removeClass('loading');
            }
      });
    }
  }
});
