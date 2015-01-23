define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

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

        onSave: function (event) {
            var url_input    = this.$("input.urlinput");
            var new_url      = url_input.val();
            var height_input = this.$("input.heightinput");
            var new_height   = height_input.val();
            var view         = this;

            helper
                .callHandler(this.model.id, "save", {url: new_url, height: new_height})
                .then(
                    // success
                    function () {
                        jQuery(event.target).addClass("accept");
                        view.switchBack();
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();
        }
    });
});
