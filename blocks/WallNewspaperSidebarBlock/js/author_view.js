define(['assets/js/author_view', 'assets/js/url', 'backbone'], function (AuthorView, helper, Backbone) {

    'use strict';

    return AuthorView.extend({

        events: {
            'click button[name=save]':   'onSave',
            'click button[name=cancel]': 'switchBack'
        },

        initialize: function() {
        },

        initializeFromDOM: function () {
        },

        postRender: function() {
            this.$('textarea').addToolbar();
        },

        // not used yet
        render: function() {
            return this;
        },

        onSave: function (event) {
            var content = this.$('textarea').val(),
                wn_id = this.$('input[type=radio]:checked').val(),
                view = this;

            event.preventDefault();

            helper
                .callHandler(this.model.id, 'save', {
                    content: content,
                    wn_id: wn_id
                })
                .then(
                    // success
                    function () {
                        Backbone.$(event.target).addClass('accept');
                        view.switchBack();
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not update the block: ' + Backbone.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();
        }

    });
});
