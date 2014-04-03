define(['assets/js/url', 'assets/js/block_model', 'assets/js/student_view', 'assets/js/block_types', 'assets/js/i18n', './edit_structure'],
       function (helper, BlockModel, StudentView, blockTypes, i18n, EditView) {

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

        children: [],

        events: {
            "click .mode-switch .student": "switchToStudentMode",
            "click .mode-switch .author":  "switchToAuthorMode",

            "click a.navigate":            "navigateTo",

            "click .add-chapter":          "addStructure",
            "click .add-subchapter":       "addStructure",
            "click .add-section":          "addStructure",

            "click .edit":                 "editStructure",

            "click .trash":                "destroyStructure"
        },

        initialize: function() {
            var $section = this.$('.active-section'),
                id = $section.attr("data-blockid"),
                section_view,
                section_model;

            section_model = new BlockModel({ id: id, type: "Section" });
            section_view = blockTypes.get("Section").createView("student", { el: $section[0], model: section_model });

            this.children.push(section_view);

            if (getHash(this.el) === "#author") {
                this.switchToAuthorMode();
            }

            this.$el.removeClass("loading");
        },

        remove: function() {
            StudentView.prototype.remove.call(this);
            _.invoke(this.children, "remove");
        },

        render: function() {
            return this;
        },

        reload: function () {
            window.location.reload(true);
        },

        // TODO: flesh this out
        navigateTo: function (event) {
            var url = jQuery(event.target).attr("href") + getHash(this.el);
            this.$el.addClass("loading");
            window.location = url;
            event.preventDefault();
        },

        switchToStudentMode: function (event) {
            this.$el.removeClass("view-author").addClass("view-student");
            clearHash(this.el);
            _.invoke(this.children, "trigger", "switch", "student");
        },

        switchToAuthorMode: function () {
            this.$el.removeClass("view-student").addClass("view-author");
            setHash(this.el, "author");
        },

        addStructure: function (event) {
            var courseware = this,
                $button = jQuery(event.target),
                $parent = $button.closest("[data-blockid]"),
                id = $parent.attr("data-blockid"),
                type;

            if (id == null) {
                return;
            }

            var model = this._newBlockFromButton($button),
                view = new EditView({ model: model }),

                insert_point = $button.closest(".controls").prev(".no-content"),
                tag = "<" + insert_point[0].tagName + "/>",
                li_wrapper = view.$el.wrap(tag).parent();

            $button.hide();
            insert_point.before(li_wrapper);
            view.focus();

            view.promise()
                .then(
                    function (model) {
                        view.$el.addClass("loading");
                        return courseware._addStructure(id, model);
                    })
                .then(
                    function (data) {
                        courseware.reload();
                    })
                .then(
                    null,
                    function (error) {
                        // TODO:  show error somehow
                        alert(error);
                        view.remove();
                        $button.show();
                    });
        },

        _newBlockFromButton: function ($button) {
            var type;

            if ($button.hasClass("add-chapter")) {
                type = "chapter";
            } else if ($button.hasClass("add-subchapter")) {
                type = "subchapter";
            } else if ($button.hasClass("add-section")) {
                type = "section";
            }

            var titles = {
                chapter: i18n("Neues Kapitel"),
                subchapter: i18n("Neues Unterkapitel"),
                section: i18n("Neue Sektion")
            };
            return new BlockModel({ title: titles[type], type: type });
        },

        _addStructure: function (parent_id, model) {
            var data = {
                parent: parent_id,
                title:  model.get("title")
            };
            return helper.callHandler(this.model.id, 'add_structure', data);
        },

        editStructure: function (event) {
            var $parent = jQuery(event.target).closest("[data-blockid]"),
                id = $parent.attr("data-blockid"),
                $title = $parent.find("> .title"),
                title = $title.find("a").text().trim();

            if (id == null) {
                return;
            }

            var type = $parent.hasClass("chapter") ? "chapter" : "subchapter";

            var model = new BlockModel({ id: id, type: type, title: title }),
                view = new EditView({ model: model });

            $title.hide().before(view.el);
            view.focus();

            view.promise()
                .then(
                    function (model) {
                        $title.find("a").text(model.get("title"));
                    },
                    function (error) {
                        alert("TODO:" + error);
                    }
                )
                .always(
                    function () {
                        view.remove();
                        $title.show();
                    });

        },

        destroyStructure: function (event) {

            var courseware = this,
                $button = jQuery(event.target),
                $parent = $button.closest("[data-blockid]"),
                id = $parent.attr("data-blockid");

            if (id == null) {
                return;
            }

            if (confirm(i18n("Wollen Sie wirklich löschen?"))) {
                $parent.addClass("loading");

                helper
                    .deleteView(id)
                    .then(
                        function () {
                            console.log(arguments);
                            // TODO: sollte zum previous sibling oder parent springen
                            courseware.reload();
                        },
                        function (error) {
                            console.log(arguments);
                            alert("ERROR:" + error);
                        });
            }
        }
    });
});
