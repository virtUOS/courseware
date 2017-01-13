define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
            "change select.cw-audioblock-source": "selectSource"
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function() {
            var $view = this;
            $view.$(".cw-audioblock-description").val($view.$(".cw-audioblock-description-stored").val());
            $view.$("select.cw-audioblock-source option[value='"+$view.$('.cw-audioblock-source-stored').val()+"']").prop("selected", true);

            if ($view.$('.cw-audioblock-source-stored').val() == "") {
                $view.$("input.cw-audioblock-file").hide();
                $view.$("select.cw-audioblock-file").show();
                $view.$(".cw-audioblock-source option[value='cw']").prop("selected", true);
            } else if ($view.$('.cw-audioblock-source-stored').val() == "cw") {
                $view.$("input.cw-audioblock-file").hide();
                $view.$(".cw-audioblock-file option[value='"+$view.$('.cw-audioblock-file-stored').val()+"']").prop("selected", true);
            } else {
                $view.$("select.cw-audioblock-file").hide();
                $view.$("input.cw-audioblock-file").val($view.$('.cw-audioblock-file-stored').val());
            }
        },

        onSave: function(event) {
            var $view = this;
            var $audiodescription = $view.$(".cw-audioblock-description").val();
            var $audiosource = $view.$(".cw-audioblock-source").val();
            if ($audiosource == "cw") {
                var $audiofile = $view.$("select.cw-audioblock-file").val();
            } else {
                var $audiofile = $view.$("input.cw-audioblock-file").val();
            }

            helper
                .callHandler(this.model.id, "save", {audio_file: $audiofile, audio_description: $audiodescription, audio_source: $audiosource})
                .then(
                    // success
                    function () {
                        jQuery(event.target).addClass("accept");
                        $view.switchBack();
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();
        }, 
        
        selectSource: function() {
            var $view = this;
            var $selection = $view.$(".cw-audioblock-source").val();
            if ($selection == "cw") {
                $view.$("input.cw-audioblock-file").hide();
                $view.$("select.cw-audioblock-file").show();
            } else {
                $view.$("select.cw-audioblock-file").hide();
                $view.$("input.cw-audioblock-file").show();
            }
            return;
        }
        
    });
});
