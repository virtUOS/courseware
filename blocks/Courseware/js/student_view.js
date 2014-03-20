define(['assets/js/url', 'assets/js/block_model', 'assets/js/student_view', 'assets/js/block_types', './edit_structure'],
       function (helper, BlockModel, StudentView, blockTypes, EditView) {

    'use strict';

    return StudentView.extend({

        children: [],

        events: {
            "click .mode-switch .student": "switchToStudentMode",
            "click .mode-switch .author":  "switchToAuthorMode",

            "click a.navigate":            "navigateTo",

            "click .add-chapter":          "addStructure",
            "click .add-subchapter":       "addStructure",
            "click .add-section":          "addStructure",

            "click .edit":                 "editStructure"
        },

        initialize: function() {
            var $section = this.$('.active-section'),
                id = $section.attr("data-blockid"),
                section_view,
                section_model;

            section_model = new BlockModel({ id: id, type: "Section" });
            section_view = blockTypes.get("Section").createView("student", { el: $section[0], model: section_model });

            this.children.push(section_view);

            this.$el.removeClass("loading");
        },

        remove: function() {
            StudentView.prototype.remove.call(this);
            _.invoke(this.children, "remove");
        },

        render: function() {
            return this;
        },

        // TODO: flesh this out
        navigateTo: function (event) {
            console.log($(event.target).text());
        },

        switchToStudentMode: function (event) {
            // this.$el.attr({ class: "view-student" });
            // helper.base_view = 'student';
            window.location.reload(true);
        },

        switchToAuthorMode: function (event) {
            this.$el.removeClass("view-student").addClass("view-author");
            helper.base_view = 'author';
        },

        addStructure: function (event) {

            var parent_id = $(event.target).closest("[data-blockid]").attr("data-blockid");

            if (parent_id == null) {
                return;
            }

            var data = {
                parent: parent_id,
                title: "Item X"
            };

            helper.callHandler(this.model.id, 'add_structure', data).then(

                function (data) {
                    window.location.reload(true);
                },

                function (error) {
                    console.log("TODO: could not add structural block");
                }
            );
        },

        editStructure: function (event) {
            var $parent = $(event.target).closest("[data-blockid]"),
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

            view.promise().then(

                // resolved
                function (model) {
                    $title.find("a").text(model.get("title")).end().show();
                },

                // rejected, just close
                function (error) {
                    $title.show();
                }
            );
        }
    });
});
