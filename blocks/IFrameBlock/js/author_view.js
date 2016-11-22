define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
            "click .submit-user-id-switch": "toggleSubmitUserId"
        },

        initialize: function(options) {
        },

        render: function() {
            
            return this;
        },
        
        postRender: function() {
            this.toggleSubmitUserId();
        },
        
        toggleSubmitUserId: function() {
            var state = this.$(".submit-user-id-switch").is( ":checked");
            console.log(state);
            if (state) this.$(".iframe-submit-user").show();
            else this.$(".iframe-submit-user").hide();
        },

        onSave: function (event) {
            var url_input    = this.$("input.urlinput");
            var new_url      = url_input.val();
            var height_input = this.$("input.heightinput");
            var new_height   = height_input.val();
            var view         = this;
            var salt         = this.$(".salt").val();
            var submit_param = this.$(".submit-param").val();
            var submit_user_id = this.$(".submit-user-id-switch").is( ":checked");

            helper
                .callHandler(this.model.id, "save", {url: new_url, height: new_height , salt: salt, submit_param: submit_param,  submit_user_id: submit_user_id}) 
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
