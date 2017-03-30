define(['backbone', 'assets/js/url', 'assets/js/block_model', 'assets/js/student_view', 'assets/js/block_types', './chapter_list',  './section_list', 'assets/js/tooltip'],
       function (Backbone, helper, BlockModel, StudentView, block_types, ChapterListView, SectionListView, tooltip) {

    'use strict';

    function getHash(el) {
        return el.ownerDocument.location.hash;
    }

    function setHash(el, fragment) {
        el.ownerDocument.location.hash = "#" + fragment;
    }

    function clearHash(el) {
        setHash(el, "");
    }

    return StudentView.extend({

        chaptersView:      null,
        sectionsView:      null,
        activeSectionView: null,
        asideSectionViews: [],

        events: {
            "click .mode-switch .student": "switchToStudentMode",
            "click .mode-switch .author":  "switchToAuthorMode",

            "click a.navigate":            "navigateTo"
        },

        initialize: function() {
            this._initializeChildren();

            if (getHash(this.el) === "#author") {
                this.switchToAuthorMode();
            }

            this.postRender();

            this.$el.removeClass("loading");
        },

        _initializeChildren: function() {
            this.activeSectionView = this._createSectionFromElement('.active-section');

            this.chaptersView = new ChapterListView({ el: '.chapters', model: this.model });
            this.sectionsView = new SectionListView({
                el: '.active-subchapter',
                model: this.model,
                active_section: this.activeSectionView.model
            });

            var aside_sections = this.$('.aside-section');
            if (aside_sections.length) {
                this.asideSectionViews = _.map(
                    aside_sections,
                    function (el) {
                        return this._createSectionFromElement(el);
                    },
                    this);
            }
        },

        _createSectionFromElement: function (el) {
            var $section = this.$(el),
                section_model = new BlockModel({
                    type:      "Section",
                    id:        $section.data('blockid'),
                    parent_id: $section.data('parentid'),
                    title:     $section.data('title')
                });

            return block_types
                .findByName("Section")
                .createView("student", { el: $section[0], model: section_model });
        },

        remove: function() {
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

        render: function() {
            return this;
        },

        postRender: function() {
            
            this.hideIndented();

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
        },

        navigateTo: function (event) {
            var navigate = true;
            event.preventDefault();
            Backbone.on('preventnavigateto', function(preventNavigateTo){
                if(preventNavigateTo){
                        navigate = false;
                }
            });
            var beforeNavigateEvent = {isUserInputHandled : false };

            Backbone.trigger("beforenavigate", beforeNavigateEvent);
            if (this.$el.hasClass("loading")) {
                return;
            }
            if (navigate){
                this.$el.addClass("loading");

                var $parent = jQuery(event.target).closest("[data-blockid]"),
                    id = $parent.attr("data-blockid");

                helper.navigateTo(id);
            }
            else return false;

        },

        switchToStudentMode: function () {
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
            Backbone.trigger("beforemodeswitch", "student", beforeModeSwitchEvent);

            if (switchView) {
                this.$el.removeClass("view-author").addClass("view-student");
                clearHash(this.el);
                Backbone.trigger("modeswitch", "student");
            }

            this.resizeColumnHeights();
        },

        switchToAuthorMode: function () {
            this.$el.removeClass("view-student").addClass("view-author");
            setHash(this.el, "author");
            Backbone.trigger("modeswitch", "author");

            this.resizeColumnHeights();
        },

        // TODO: fix CSS layout to remove this ugly workaround
        // see https://github.com/virtUOS/courseware/issues/71
        resizeColumnHeights: function () {
            this.$el.css('min-height', this.$('> aside').height() + 'px');
        },

        hideIndented: function() {
            var $view = this;
            var $indented = $view.$(".indented");
            $indented.parents(".subchapter").hide();

            $.each($indented, function(){
                $(this).parents(".subchapter").addClass("indented");
                var $prev = $(this).parents(".subchapter").prev();
                var $active = $prev.hasClass("selected");
                if($active) {
                    $(this).parents(".subchapter").addClass("indentedsibling");
                }
                var $brother = $prev.hasClass("indentedsibling");
                if ($active || $brother) {
                    $(this).parents(".subchapter").show();
                    $(this).parents(".subchapter").addClass("indentedsibling");
                }
            });

            $view.$(".subchapter").each(function(){
                if ($(this).hasClass("selected")&& $(this).hasClass("indented")) {
                    $(this).show();
                    var $prev = $(this).prevUntil(".subchapter:not('.indented')");
                    $prev.each(function(){
                            $(this).show();
                    });
                }
                
            });

        }
    });
});
