define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    'use strict';

    return StudentView.extend({
        events: {
            'click button[name=reset-exercise]': function (event) {
                var $form = this.$(event.target).closest('form');
                var view = this;

                if (confirm('Soll die Antwort zurückgesetzt werden?')) {
                    helper
                        .callHandler(this.model.id, 'exercise_reset', $form.serialize())
                        .then(
                            function () {
                                view.renderServerSide();
                            },
                            function () {
                                console.log('failed to reset the exercise');
                            }
                        );
                }

                return false;
            },
            'click button[name=submit-exercise]': function (event) {
                var $form = this.$(event.target).closest('form');
                var view = this;

                helper
                    .callHandler(this.model.id, 'exercise_submit', $form.serialize())
                    .then(
                        function () {
                            view.renderServerSide();
                        },
                        function () {
                            console.log('failed to store the solution');
                        }
                    );

                return false;
            }
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function () {
            var view = this;
            var $sortableAnswers = jQuery('ul.exercise_answers', this.$el);

            if ($sortableAnswers.length == 1) {
                this.updateSizes(null);
                $sortableAnswers.sortable({
                    axis: 'y',
                    containment: $sortableAnswers,
                    tolerance: 'pointer',
                    update: function () {
                        view.moveChoice($sortableAnswers);
                    },
                    sort: function (event, ui) {
                        // this workaround is needed, otherwise, sortable items
                        // would jump when the user scrolled down before sorting
                        ui.helper.css({
                            top : ui.position.top + $(window).scrollTop() + 'px'
                        });
                    }
                });
            }
        },

        updateSizes: function (event) {
            var $columns = jQuery('ul.matching_exercise', this.$el);
            var $items = jQuery('li', $columns);
            var height = 0;

            if (event != null) {
                $items.css('height', 'auto');
                $columns.css('width', 'auto');
            }

            $items.each(function (i, element) {
                height = Math.max(height, jQuery(element).height());
            });

            $items.height(height);
            $columns.width(function (index, width) {
                return width;
            });
        },

        moveChoice: function ($sortableAnswers) {
            var items = $sortableAnswers.sortable('toArray');
            var $inputs = jQuery('input', $sortableAnswers);

            for (var i = 0; i < items.length; i++) {
                $inputs.eq(i).val(i);
            }
        }
    });
});
