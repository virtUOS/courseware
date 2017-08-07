define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
            "click .submit-user-id-switch": "toggleSubmitUserId",
            "click .iframe-cc-switch": "toggleCCSwitch",
            "change .iframe-cc-license": "CCSelect"
        },

        initialize: function(options) {
            Backbone.on('beforemodeswitch', this.onModeSwitch, this);
            Backbone.on('beforenavigate', this.onNavigate, this);
        },

        render: function() {
            
            return this;
        },
        
        postRender: function() {
            this.toggleSubmitUserId();
            this.toggleCCSwitch();
            this.setCCInfos();
            this.CCSelect();
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
        
        toggleSubmitUserId: function() {
            var state = this.$(".submit-user-id-switch").is( ":checked");
            if (state) this.$(".iframe-submit-user").show();
            else this.$(".iframe-submit-user").hide();
        },
        
        toggleCCSwitch: function() {
            var state = this.$(".iframe-cc-switch").is( ":checked");
            if (state) this.$(".iframe-cc-wrapper").show();
            else this.$(".iframe-cc-wrapper").hide();
        },
        
        CCSelect: function() {
            var license = this.$(".iframe-cc-license").val();
            var icon = this.$(".iframe-cc-icon");
            icon.attr("class", "");
            icon.addClass("iframe-cc-icon");
            icon.addClass(license);
        
        },
        
        setCCInfos: function() {
            if (this.$('.iframe-cc-infos-stored').val() == "") {return;}
            var cc_infos_stored = $.parseJSON(this.$('.iframe-cc-infos-stored').val())[0];
            this.$(".iframe-cc-license option[value='"+cc_infos_stored.cc_type+"']").prop("selected", true);
            this.$(".iframe-cc-work-name").val(cc_infos_stored.cc_work_name);
            this.$(".iframe-cc-work-url").val(cc_infos_stored.cc_work_url);
            this.$(".iframe-cc-author-name").val(cc_infos_stored.cc_author_name);
            this.$(".iframe-cc-author-url").val(cc_infos_stored.cc_author_url);
            this.$(".iframe-cc-license-name").val(cc_infos_stored.cc_license_name);
            this.$(".iframe-cc-license-url").val(cc_infos_stored.cc_license_url);
        },

        onSave: function (event) {
            var url_input       = this.$("input.urlinput");
            var new_url         = url_input.val();
            var height_input    = this.$("input.heightinput");
            var new_height      = height_input.val();
            var view            = this;
            var salt            = this.$(".salt").val();
            var submit_param    = this.$(".submit-param").val();
            var submit_user_id  = this.$(".submit-user-id-switch").is( ":checked");
            var show_cc         = this.$(".iframe-cc-switch").is( ":checked");
            var cc_type         = this.$(".iframe-cc-license").val();
            var cc_work_name    = this.$(".iframe-cc-work-name").val();
            var cc_work_url     = this.$(".iframe-cc-work-url").val();
            var cc_author_name  = this.$(".iframe-cc-author-name").val();
            var cc_author_url   = this.$(".iframe-cc-author-url").val();
            var cc_license_name = this.$(".iframe-cc-license-name").val();
            var cc_license_url  = this.$(".iframe-cc-license-url").val();
            if (show_cc) {
                var cc_infos = new Array();
                cc_infos.push({
                    "cc_type"           : cc_type, 
                    "cc_work_name"      : cc_work_name,
                    "cc_work_url"       : cc_work_url, 
                    "cc_author_name"    : cc_author_name,
                    "cc_author_url"     : cc_author_url,
                    "cc_license_name"   : cc_license_name,
                    "cc_license_url"    : cc_license_url
                });
                cc_infos = JSON.stringify(cc_infos);
            } else {
                var cc_infos = ""; 
            }
            helper
                .callHandler(this.model.id, "save", {url: new_url, height: new_height , salt: salt, submit_param: submit_param,  submit_user_id: submit_user_id, cc_infos: cc_infos}) 
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
