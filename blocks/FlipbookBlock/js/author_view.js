define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
            "click input[type=file]":    "loadfile",
            

        },

        initialize: function() {
            var $section = this.$el.closest('section.FlipbookBlock');
            var $sortingButtons = jQuery('button.lower', $section);
            $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
            $sortingButtons.addClass('no-sorting');

            Backbone.on('beforemodeswitch', this.onModeSwitch, this);
            Backbone.on('beforenavigate', this.onNavigate, this);
        },

        onNavigate: function(event){
        if(!$("section .block-content button[name=save]").length) return;
        if(event.isUserInputHandled) return;
            event.isUserInputHandled = true;
            Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));

    },

        postRender: function() {
        },
        
        loadfile: function() {
            // remove selection
        },

        // not used yet
        render: function() {
            return this;
        },

        onSave: function (event) {
            var $pdf = this.$el.find(".flipbook-pdf"),
                view = this;
                
            var $pdf_name = $pdf.val();
            var $pdf_id = $pdf.find('option:selected').attr("pdf_id");
            var $pdf_filename = $pdf.find('option:selected').attr("filename");
            console.log($pdf_id );
                
            //textarea.remove();
            helper
                .callHandler(this.model.id, "save", {pdf: $pdf_name, pdf_id: $pdf_id, pdf_filename: $pdf_filename})
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
