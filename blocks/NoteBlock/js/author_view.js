define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
            "change select[name=cw-noteblock-type]": "selectType"
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function() {
            var $view = this;
            var $notetype = $view.$(".cw-noteblock-type-stored").val();
            var $notecolor = $view.$(".cw-noteblock-color-stored").val();
            var $notequantity = $view.$(".cw-noteblock-quantity-stored").val();
            if($notetype != "") {
                $view.$(".cw-noteblock-type option[value='"+$notetype+"']").prop("selected", true);
                if ($notetype == "classic") {
                    $view.$(".cw-noteblock-color").prop("disabled", true);
                    $view.$(".cw-noteblock-quantity").prop("disabled", true);
                }
            }
            if($notecolor != "") {
                $view.$(".cw-noteblock-color option[value='"+$notecolor+"']").prop("selected", true);
            }
            if($notequantity != "") {
                $view.$(".cw-noteblock-quantity").val($notequantity);
            }
        },

        onSave: function(event) {
            var $view = this;
            var $notetype = $view.$(".cw-noteblock-type").val();
            var $notecolor = $view.$(".cw-noteblock-color").val();
            var $notequantity = $view.$(".cw-noteblock-quantity").val();

            //textarea.remove();
            helper
                .callHandler(this.model.id, "save", {note_type: $notetype, note_color: $notecolor, note_quantity: $notequantity})
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
        
        selectType: function() {
            var $view = this;
            var $notetype = $view.$(".cw-noteblock-type").val();
            var $notecolor = $view.$(".cw-noteblock-color");
            var $notequantity = $view.$(".cw-noteblock-quantity");
            
            if ($notetype == "classic") {
                    $notequantity.val(1).prop("disabled", true);
                    $notecolor.find("option[value='white']").prop("selected", true)
                    $notecolor.prop("disabled", true);
            }
            else {
                $notequantity.prop("disabled", false);
                $notecolor.prop("disabled", false);
            }
        }
        
    });
});
