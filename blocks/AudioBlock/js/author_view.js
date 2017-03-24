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
            Backbone.on('beforemodeswitch', this.onModeSwitch, this);
            Backbone.on('beforenavigate', this.onNavigate, this);
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

        onNavigate: function(event){
            if(!$("section .block-content button[name=save]").length) {
                return;
            }
            if(event.isUserInputHandled) {
                return;
            }
            event.isUserInputHandled = true;
            Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
        },
        
        onModeSwitch: function (toView, event) {
            if (toView != 'student') {
                return;
            }
            // the user already switched back (i.e. the is not visible)
            if (!this.$el.is(':visible')) {
                return;
            }
            // another listener already handled the user's feedback
            if (event.isUserInputHandled) {
                return;
            }
            event.isUserInputHandled = true;
            Backbone.trigger('preventviewswitch', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie trotzdem fortfahren?'));
        }, 

        onSave: function(event) {
            var $view = this;
            var $audiodescription = $view.$(".cw-audioblock-description").val();
            var $audiosource = $view.$(".cw-audioblock-source").val();
            if ($audiosource == "cw") {
                var $audiofile = $view.$("select.cw-audioblock-file").val();
                var $audioid = $view.$("select.cw-audioblock-file option:selected").attr("document-id");
            } else {
                var $audiofile = $view.$("input.cw-audioblock-file").val();
                var audioid = "";
            }

            helper
                .callHandler(this.model.id, "save", {audio_file: $audiofile, audio_id: $audioid, audio_description: $audiodescription, audio_source: $audiosource})
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
                $view.$("label[for='cw-audioblock-file']").html("Audiodatei:");
            } else {
                $view.$("select.cw-audioblock-file").hide();
                $view.$("input.cw-audioblock-file").show();
                $view.$("label[for='cw-audioblock-file']").html("URL:");
            }
            return;
        }

    });
});
