define(['assets/js/url', 'assets/js/block_model', 'assets/js/block_view', 'assets/js/block_types'],
       function (helper, BlockModel, BlockView, blocks) {

    'use strict';

    var Courseware = BlockView.extend({

        // TODO: put this into the super 'class'
        view_name: "student",

        children: [],

        events: {
            "click button.student": "switchToStudentMode",
            "click button.author":  "switchToAuthorMode",
            "click li.chapter": "debug"
        },

        initialize: function() {
            var $section = this.$('.active-section'),
                id = $section.attr("data-id"),
                section_view,
                section_model;

            section_model = new BlockModel({ id: id, type: "Section" });
            section_view = blocks.get("Section").createView("student", { el: $section[0], model: section_model });

            this.children.push(section_view);
        },

        remove: function() {
            BlockView.prototype.remove.call(this);
            _.invoke(this.children, "remove");
        },

        render: function() {
            return this;
        },

        debug: function (event) {
            alert($(event.target).text());
        },

        switchToStudentMode: function (event) {
            // this.$el.attr({ class: "view-student" });
            // helper.base_view = 'student';
            window.location = "";
        },

        switchToAuthorMode: function (event) {
            this.$el.attr({ "class": "view-author" });
            helper.base_view = 'author';
        }
    });

    return Courseware;
});
