define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack"

        },

        initialize: function() {
            Backbone.on('beforemodeswitch', this.onModeSwitch, this);
            Backbone.on('beforenavigate', this.onNavigate, this);
        },

        initializeFromDOM: function() {
            var $section = this.$el.closest('section.ForumBlock');
            var $sortingButtons = jQuery('button.lower', $section);
            $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
            $sortingButtons.addClass('no-sorting');
        },

        onNavigate: function(event){
            if(!$("section .block-content button[name=save]").length) return;
            if(event.isUserInputHandled) return;
            event.isUserInputHandled = true;
            Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
        },

        onSave: function (event) {
            var area = this.$("select[name=area]").val(),
                view = this;

            helper
                .callHandler(this.model.id, "save", {area_id: area})
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
                    });
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

    });
});
