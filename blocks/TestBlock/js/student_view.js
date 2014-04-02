define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    'use strict';

    return StudentView.extend({
        events: {
            'click button': function (event) {
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
        }
    });
});
