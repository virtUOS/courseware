define(['assets/js/student_view'],
       function (StudentView) {

    'use strict';

    return StudentView.extend({
        events: {
        },

        initialize: function() {
            var $section = this.$el.closest('section.HtmlBlock');
            var $sortingButtons = jQuery('button.lower', $section);
            $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
            $sortingButtons.removeClass('no-sorting');
        },

        render: function() {
            return this;
        },

        postRender: function () {
            MathJax.Hub.Queue(["Typeset", MathJax.Hub, this.el]);
        }
    });
});
