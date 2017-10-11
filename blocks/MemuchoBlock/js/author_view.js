define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "reload",
            "change input.scriptinput" : "showData",
            "keyup input.scriptinput" : "showData",
            "paste input.scriptinput" : "showData"
        },

        initialize: function(options) {
        },

        render: function() {
            
            return this;
        },

        postRender: function() {

        },

        reload: function() {
            location.reload();
        },

        showData: function() {
            var view = this;
            try {
                var xmlDoc = $.parseXML(view.$("input.scriptinput").val());
                var $script = $(xmlDoc).find("script");
                
                this.$(".memucho-data-id").val($script.attr("data-id"));
                this.$(".memucho-data-t").val($script.attr("data-t"));
                this.$(".memucho-data-questionCount").val($script.attr("data-questionCount"));
             } catch (error) {
                console.log(error);
            }
        },

        onSave: function (event) {
            var view = this;
            var data_id = view.$(".memucho-data-id-stored").val(); 
            var data_t =  view.$(".memucho-data-t-stored").val(); 
            var data_questionCount = view.$(".memucho-data-questionCount-stored").val();
            var $script = "";
            try {
                var xmlDoc = $.parseXML(view.$("input.scriptinput").val());
                $script = $(xmlDoc).find("script");
                
            } catch (error) {
                console.log(error);
            }
            if ($script != "") {
                data_id = $script.attr("data-id"); 
                data_t =  $script.attr("data-t"); 
                data_questionCount = $script.attr("data-questionCount"); 
            }
            helper
                .callHandler(view.model.id, "save", {
                    'data_id': data_id,
                    'data_t' : data_t,
                    'data_questionCount' : data_questionCount
                }) 
                .then(
                    // success
                    function () {
                        //has to reload to load memucho script properly
                        location.reload();
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
