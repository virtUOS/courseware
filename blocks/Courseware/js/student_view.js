import jQuery from 'jquery'
import Backbone from 'backbone'
import _ from 'underscore'
import helper from 'js/url'
import BlockModel from 'js/block_model'
import StudentView from 'js/student_view'
import BlockTypes from 'js/block_types'
import ChapterListView from './chapter_list'
import SectionListView from './section_list'
import tooltip from 'js/tooltip'

function getHash(el) {
  return el.ownerDocument.location.hash;
}

function setHash(el, fragment) {
  el.ownerDocument.location.hash = '#' + fragment;
}

function clearHash(el) {
  setHash(el, '');
}

export default StudentView.extend({
  chaptersView:      null,
  sectionsView:      null,
  activeSectionView: null,
  asideSectionViews: [],

  events: {
    'click .mode-switch .student': 'switchToStudentMode',
    'click .mode-switch .author':  'switchToAuthorMode',
    'click .mobile-show-nav-button' : 'showMobileNavigation',
    'click a.navigate':            'navigateTo'
  },

  initialize() {
    this._initializeChildren();

    if (getHash(this.el) === '#author') {
      this.switchToAuthorMode();
    }
    this.postRender();
    this.$el.removeClass('loading');
  },

  _initializeChildren() {
    this.activeSectionView = this._createSectionFromElement('.active-section');

    this.chaptersView = new ChapterListView({ el: '.chapters', model: this.model });
    this.sectionsView = new SectionListView({
      el: '.active-subchapter',
      model: this.model,
      active_section: this.activeSectionView.model
    });
    var aside_sections = this.$('.aside-section');
    if (aside_sections.length) {
      this.asideSectionViews = aside_sections.map((index, el) => {
        return this._createSectionFromElement(el);
      });
    }
  },

  _createSectionFromElement(el) {
    const $section = this.$(el)
    const section_model = new BlockModel({
      type:      'Section',
      id:        $section.data('blockid'),
      parent_id: $section.data('parentid'),
      title:     $section.data('title')
    });
    return BlockTypes
      .findByName('Section')
      .createView('student', { el: $section[0], model: section_model })
  },

  remove() {
    StudentView.prototype.remove.call(this);
    if (this.asideSectionViews.length) {
      _.invoke(this.asideSectionViews, 'remove');
    }
    if (this.chaptersView) {
      this.chaptersView.remove();
    }
    if (this.sectionsView) {
      this.sectionsView.remove();
    }
    if (this.activeSectionView) {
      this.activeSectionView.remove();
    }
  },

  render() {
    return this;
  },

  postRender() {
    if (this.asideSectionViews.length) {
      _.invoke(this.asideSectionViews, 'postRender');
    }
    if (this.chaptersView) {
      this.chaptersView.postRender();
    }
    if (this.sectionsView) {
      this.sectionsView.postRender();
    }
    if (this.activeSectionView) {
      this.activeSectionView.postRender();
    }
    tooltip(this.$el, 'button');
    this.resizeColumnHeights();

    if ($(".mobile-show-nav-button").is(":visible")) {
        this.$el.find(".aside-section").appendTo("#courseware");
    }
    
  },

  navigateTo(event) {
    var navigate = true;
    event.preventDefault();
    Backbone.on('preventnavigateto', function (preventNavigateTo) {
      if (preventNavigateTo) {
        navigate = false;
      }
    });
    var beforeNavigateEvent = { isUserInputHandled : false };

    Backbone.trigger('beforenavigate', beforeNavigateEvent);
    if (this.$el.hasClass('loading')) {
      return;
    }
    if (navigate) {
      this.$el.addClass('loading');

      const $parent = jQuery(event.target).closest('[data-blockid]');
      const id = $parent.attr('data-blockid');

      helper.navigateTo(id);
      return
    }

    return false;
  },

  switchToStudentMode() {
    var switchView = true;

    // Listen on the "preventviewswitch" event, other parts of the
    // application can listen to the "beforemodeswitch" event. If
    // they want to prevent the switch of the view, they'll trigger
    // such a "preventviewswitch" event passing true to the
    // listeners.
    Backbone.on('preventviewswitch', function (preventViewSwitch) {
      if (preventViewSwitch) {
        switchView = false;
      }
    });

    // notify listeners that the view should be switched
    var beforeModeSwitchEvent = {
      fromView: 'author',
      toView: 'student',
      isUserInputHandled: false
    };
    Backbone.trigger('beforemodeswitch', 'student', beforeModeSwitchEvent);

    if (switchView) {
      this.$el.removeClass('view-author').addClass('view-student');
      clearHash(this.el);
      Backbone.trigger('modeswitch', 'student');
    }

    this.resizeColumnHeights();
  },

  switchToAuthorMode() {
    this.$el.removeClass('view-student').addClass('view-author');
    setHash(this.el, 'author');
    Backbone.trigger('modeswitch', 'author');
    this.resizeColumnHeights();
  },

  // TODO: fix CSS layout to remove this ugly workaround
  // see https://github.com/virtUOS/courseware/issues/71
  resizeColumnHeights() {
    this.$el.css('min-height', this.$('> aside').height() + 'px');
  },
  
  showMobileNavigation(event) {
    if (jQuery(event.target).hasClass("nav-on")) {
        this.$el.find(".cw-sidebar").hide("slow");
        jQuery(event.target).removeClass("nav-on");
        this.$el.find(".breadcrumb").show();
    } else {
        this.$el.find(".cw-sidebar").show("slow");
        this.$el.find(".breadcrumb").hide();
        jQuery(event.target).addClass("nav-on");
    }
  }
});
