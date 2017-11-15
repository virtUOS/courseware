import jQuery from 'jquery'
import Backbone from 'backbone'
import helper from 'js/url'
import templates from 'js/templates'
import i18n from 'js/i18n'
import BlockModel from 'js/block_model'
import tooltip from 'js/tooltip'

import EditView from './edit_structure'

export default Backbone.View.extend({

  events: {
    'click .add-section':       'addStructure',
    'click .init-sort-section': 'initSorting',
    'click .stop-sort-section': 'stopSorting'
  },

  initialize(options) {
    this.listenTo(Backbone, 'modeswitch', this.stopSorting, this);
    this.active_section = options.active_section;
    this.listenTo(this.active_section, 'change', this.updateSectionList, this);
  },

  render() {
    return this;
  },

  postRender() {
    tooltip(this.$el, 'li.prev,li.section,li.next', function () { return jQuery(this).find('a').attr('data-title') });
    this.makeSticky();
  },

  makeSticky() {
    const nav = this.$el;
    const nav_offset = nav.offset();

    jQuery(window).scroll(function () {
      // if we scrolled a bit and are not an author! the scrollbar should be stuck to the window
      if (jQuery(this).scrollTop() > nav_offset.top - 27 && (window.location.href.indexOf('author') == -1)) {
        var activeSection = jQuery('section.active-section');
        nav.addClass('stuck');
        nav.css({ left: activeSection.offset().left });
      } else {
        nav.removeClass('stuck');
        nav.css({ left: '' });
      }
    });
  },

  addStructure() {
    var id = this.$el.attr('data-blockid');

    if (id === null) {
      return;
    }

    var model = new BlockModel({ title: i18n('Neuer Abschnitt'), type: 'Section' }),
        view = new EditView({ model: model }),
        insert_point = this.$('.no-content'),
        li_wrapper = view.$el.wrap('<li/>').parent(),
        self = this,
        $controls = this.$('.controls'),
        placeholder_item;

    $controls.hide();
    insert_point.before(li_wrapper);
    view.postRender();

    view.promise()
      .then(function (model) {
        placeholder_item = insert_point
          .before(templates('Courseware', 'section', model.toJSON()))
          .prev()
          .addClass('loading');

        return self._addStructure(id, model);
      })
      .then(function (data) {
        placeholder_item.replaceWith(templates('Courseware', 'section', data));
        li_wrapper.remove();
        $controls.show();
      })
      .catch(function (error) {
        if (placeholder_item) {
          placeholder_item.remove();
        }

        if (error) {
          var errorMessage = 'Could not add the section: ' + jQuery.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        }
        li_wrapper.remove();
        $controls.show();
      });
  },

  _addStructure(parent_id, model) {
    var data = {
      parent: parent_id,
      title:  model.get('title')
    };

    return helper.callHandler(this.model.id, 'add_structure', data);
  },

  _sortable: null,

  _original_positions: null,

  _get_positions() {
    return this.$el.sortable('toArray', { attribute: 'data-blockid' });
  },

  initSorting() {
    if (this._sortable) {
      throw 'Already sorting!';
    }
    this._sortable = this.$el;
    this._sortable.sortable({
      items:       '.section',
      handle:      '.handle',
      axis:        'x',
      tolerance:   'pointer',
      distance:    5,
      placeholder: 'sortable-placeholder'
    });
    this._original_positions = this._get_positions();
    this.$el.addClass('sorting');
  },

  stopSorting() {
    if (!this._sortable) {
      return;
    }
    var positions = this._get_positions(),
        subchapter_id = this._sortable.attr('data-blockid'),
        data;
    this._sortable.sortable('destroy');

    if (JSON.stringify(positions) !== JSON.stringify(this._original_positions)) {
      data = {
        parent:    subchapter_id,
        positions: positions
      };
      helper.callHandler(this.model.id, 'update_positions', data);
    }

    this._sortable = null;
    this._original_positions = null;
    this.$el.removeClass('sorting');
  },

  updateSectionList() {
    if (this.active_section.hasChanged('title')) {
      this.$('> .selected > a')
        .attr({
          title:        this.active_section.get('title'),
          'data-title': this.active_section.get('title')
        });
    }
  }
});
