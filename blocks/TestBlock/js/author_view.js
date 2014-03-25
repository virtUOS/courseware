define(['assets/js/author_view', 'assets/js/url'], function (AuthorView, helper) {
    'use strict';

    return AuthorView.extend({

        events: {
            "click button": function () {
                var view = this;

                helper
                    .callHandler(this.model.id, 'modify_test', this.$('input').val())
                    .then(
                        function () {
                            view.switchBack();
                        },
                        function () {
                            alert('test modification failed');
                        }
                    );
            }
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        }
    });
});
