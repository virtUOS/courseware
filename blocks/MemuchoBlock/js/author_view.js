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
        
        postRender: function() {

        },
        

        onSave: function (event) {
            var script_input    = this.$("input.scriptinput");
            var new_script      = script_input.val();
           
            var view         = this;


            helper
                .callHandler(this.model.id, "save", {'data_id': new_script
                                                          }) 

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
        
            location.reload();
        
        }
    });
});
