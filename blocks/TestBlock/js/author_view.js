define(['assets/js/author_view', 'assets/js/url'], function (AuthorView, helper) {
    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack"
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        onSave: function () {
            var view = this;

            helper
                .callHandler(this.model.id, 'modify_test', this.$('select[name="test_id"]').val())
                .then(
                    function () {
                        view.switchBack();
                    },
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    }
                ).done();
        }
    });
});
