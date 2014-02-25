define(['require', 'backbone', 'assets/js/url', 'assets/js/block_types'], function (require, Backbone, helper, blocks) {

    'use strict';

    var Courseware = Backbone.View.extend({

        children: [],

        events: {
            "click button.student": "switchToStudentMode",
            "click button.author":  "switchToAuthorMode",
            "click li.chapter": "debug"
        },

        initialize: function() {
            var $section = this.$('.active-section'),
                id = $section.attr("data-id"),
                section_view;

            section_view = blocks.get("Section").createView("student", { el: $section[0], block_id: id });

            this.children.push(section_view);
        },

        remove: function() {
            Backbone.View.prototype.remove.call(this);
            _.invoke(this.children, "remove");
        },

        render: function() {
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
