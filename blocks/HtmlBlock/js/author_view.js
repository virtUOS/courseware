define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack"
        },

        initialize: function(options) {
            Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        },

        postRender: function() {
            this.$("textarea").addToolbar();
        },

        // not used yet
        render: function() {
            return this;
        },

        onSave: function (event) {
            var textarea = this.$("textarea"),
                new_val = textarea.val(),
                view = this;

            //textarea.remove();
            helper
                .callHandler(this.model.id, "save", {content: new_val})
                .then(
                    // success
                    function () {
                        jQuery(event.target).addClass("accept");
                        view.switchBack();
                    },

                    // error
                    function () {
                        alert("Fehler, TODO!");
                        console.log("fail", arguments);
                    });
        },

        onModeSwitch: function (toView, event) {
            if (toView != 'student') {
                return;
            }

            // another listener already handled the user's feedback
            if (event.isUserInputHandled) {
                return;
            }

            event.isUserInputHandled = true;
            Backbone.trigger('preventviewswitch', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie trotzdem fortfahren?'));
        }
    });
});
