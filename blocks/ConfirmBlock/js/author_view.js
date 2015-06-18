define(['assets/js/author_view', 'assets/js/url'], function (
    AuthorView, helper
) {
    'use strict';
    return AuthorView.extend({
        events: {
            'click button[name="save"]':   'onSave',
            'click button[name="cancel"]': 'switchBack'
        },
        initialize: function(options) {
        },
        render: function() {
            return this;
        },
        postRender: function() {
        },

        onSave: function (event) {
            var input = this.$('input[name="title"]'),
                button = $(event.target),
                new_title = input.val().trim(),
                view = this;

            if (new_title === '') {
                return;
            }

            // disable button for now
            button.prop('disabled', true);

            this.model.set('title', new_title);

            this.model.save()
                .then(
                    // success
                    function () {
                        view.switchBack();
                    },

                    // error
                    function (error) {
                        button.prop('disabled', false);

                        var errorMessage = 'Could not update the title: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();
        }
    });
});
