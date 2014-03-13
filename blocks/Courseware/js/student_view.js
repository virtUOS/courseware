define(['assets/js/url', 'assets/js/block_model', 'assets/js/student_view', 'assets/js/block_types'],
       function (helper, BlockModel, StudentView, blockTypes) {

    'use strict';

    return StudentView.extend({

        children: [],

        events: {
            "click .mode-switch .student": "switchToStudentMode",
            "click .mode-switch .author":  "switchToAuthorMode",

            "click a.navigate":            "navigateTo",

            "click .add-chapter":          "addStructure",
            "click .add-subchapter":       "addStructure",
            "click .add-section":          "addStructure"
        },

        initialize: function() {
            var $section = this.$('.active-section'),
                id = $section.attr("data-blockid"),
                section_view,
                section_model;

            section_model = new BlockModel({ id: id, type: "Section" });
            section_view = blockTypes.get("Section").createView("student", { el: $section[0], model: section_model });

            this.children.push(section_view);
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
            window.location = "";
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
                    debugger;
                },

                function (error) {
                    console.log("TODO: could not add structural block");
                }
            );
        }
    });
});
