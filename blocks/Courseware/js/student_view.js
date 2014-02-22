define(['require', 'backbone', 'assets/js/url', 'block!Section'], function (require, Backbone, helper, SectionViews) {

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

            section_view = new SectionViews.student({ el: $section[0], block_id: id });

            this.children.push(section_view);
        },

        render: function() {
        },

        debug: function (event) {
            alert($(event.target).text());
        },

        switchToStudentMode: function (event) {
            this.$el.attr({ class: "view-student" });
            helper.base_view = 'student';
        },

        switchToAuthorMode: function (event) {
            this.$el.attr({ class: "view-author" });
            helper.base_view = 'author';
        }

    });

    return Courseware;
});
