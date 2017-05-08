define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
            "change select[name=cw-noteblock-type]": "selectType",
            "change input[name=cw-noteblock-quantity]": "selectQuantity"
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
            var $noteheader1 = $view.$(".cw-noteblock-header1-stored").val();
            var $noteheader2 = $view.$(".cw-noteblock-header2-stored").val();
            var $notequestions = $view.$(".cw-noteblock-questions-stored").val();
            if($notetype != "") {
                $view.$(".cw-noteblock-type option[value='"+$notetype+"']").prop("selected", true);
                if ($notetype == "classic") {
                    $view.$(".cw-noteblock-color").prop("disabled", true);
                    //$view.$(".cw-noteblock-quantity").prop("disabled", true);
                }
            }
            if($notecolor != "") {
                $view.$(".cw-noteblock-color option[value='"+$notecolor+"']").prop("selected", true);
            }
            if($notequantity != "") {
                $view.$(".cw-noteblock-quantity").val($notequantity);
            } else {
                $view.$(".cw-noteblock-quantity").val(1);
            }
            if($noteheader1 != "") {
                $view.$(".cw-noteblock-header1").val($noteheader1);
            }
            if($noteheader2 != "") {
                $view.$(".cw-noteblock-header2").val($noteheader2);
            }
            if($notequestions != "") {
                $.each(JSON.parse($notequestions), function($key, $value){
                    $('<input type="text" class="cw-noteblock-questions" name="cw-noteblock-questions" value="'+$value+'">').insertAfter($view.$(".cw-noteblock-questions").last());

                });
                $view.$(".cw-noteblock-questions").first().remove();
                
            }
        },

        onSave: function(event) {
            var $view = this;
            var $notetype = $view.$(".cw-noteblock-type").val();
            var $notecolor = $view.$(".cw-noteblock-color").val();
            var $notequantity = $view.$(".cw-noteblock-quantity").val();
            var $noteheader1 = $view.$(".cw-noteblock-header1").val();
            var $noteheader2 = $view.$(".cw-noteblock-header2").val();
            var $notequestions = $view.$(".cw-noteblock-questions");
            var $notequestionsval = new Array();
            $notequestions.each(function(){
                    $notequestionsval.push($(this).val());
            });
            $notequestionsval = JSON.stringify($notequestionsval);
            console.log($notequestionsval);
            helper
                .callHandler(this.model.id, "save", {
                    note_type: $notetype,
                    note_color: $notecolor,
                    note_quantity: $notequantity,
                    note_header1: $noteheader1,
                    note_header2: $noteheader2,
                    note_questions: $notequestionsval
                })
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
                    //$notequantity.val(1).prop("disabled", true);
                    $notecolor.find("option[value='white']").prop("selected", true)
                    $notecolor.prop("disabled", true);
                    this.selectQuantity();
            }
            else {
                $notequantity.prop("disabled", false);
                $notecolor.prop("disabled", false);
                $view.$(".cw-noteblock-questions").not(':first').remove();
            }
        },
        
        selectQuantity: function() {
            var $view = this;
            var $notetype = $view.$(".cw-noteblock-type").val();
            var $notequantity = $view.$(".cw-noteblock-quantity").val();
            var $notequestions = $view.$(".cw-noteblock-questions");
            
            if ($notetype == "classic") {
                do {
                    if($notequantity > $view.$(".cw-noteblock-questions").length){
                        //clone
                        $('</label><input type="text" class="cw-noteblock-questions" name="cw-noteblock-questions">').insertAfter($view.$(".cw-noteblock-questions").last());
                    }
                    if($notequantity < $view.$(".cw-noteblock-questions").length){
                        //clone
                        $view.$(".cw-noteblock-questions").last().remove();
                    }
                } while ($notequantity != $view.$(".cw-noteblock-questions").length) 
            }
            else {

            }
        }
        
    });
});
