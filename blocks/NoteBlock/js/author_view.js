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
                $.each(JSON.parse($noteheader2), function($key, $value){
                    $('<input type="text" class="cw-noteblock-header2" name="cw-noteblock-header2" value="'+$value+'">').insertAfter($view.$(".cw-noteblock-header2").last());

                });
                $view.$(".cw-noteblock-header2").first().remove();
                
            }
        },

        onSave: function(event) {
            var $view = this;
            var $notetype = $view.$(".cw-noteblock-type").val();
            var $notecolor = $view.$(".cw-noteblock-color").val();
            var $notequantity = $view.$(".cw-noteblock-quantity").val();
            var $noteheader1 = $view.$(".cw-noteblock-header1").val();
            var $noteheader2 = $view.$(".cw-noteblock-header2");
            var $noteheader2val = new Array();
            $noteheader2.each(function(){
                    $noteheader2val.push($(this).val());
            });
            $noteheader2val = JSON.stringify($noteheader2val);
            console.log($noteheader2val);
            helper
                .callHandler(this.model.id, "save", {note_type: $notetype, note_color: $notecolor, note_quantity: $notequantity, note_header1: $noteheader1, note_header2: $noteheader2val})
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
                $view.$(".cw-noteblock-header2").not(':first').remove();
            }
        },
        
        selectQuantity: function() {
            var $view = this;
            var $notetype = $view.$(".cw-noteblock-type").val();
            var $notequantity = $view.$(".cw-noteblock-quantity").val();
            var $noteheader2 = $view.$(".cw-noteblock-header2");
            
            if ($notetype == "classic") {
                do {
                    if($notequantity > $view.$(".cw-noteblock-header2").length){
                        //clone
                        $('</label><input type="text" class="cw-noteblock-header2" name="cw-noteblock-header2">').insertAfter($view.$(".cw-noteblock-header2").last());
                    }
                    if($notequantity < $view.$(".cw-noteblock-header2").length){
                        //clone
                        $view.$(".cw-noteblock-header2").last().remove();
                    }
                } while ($notequantity != $view.$(".cw-noteblock-header2").length) 
            }
            else {

            }
        }
        
    });
});
