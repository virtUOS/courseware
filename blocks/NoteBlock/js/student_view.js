define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    
    'use strict';
    
    return StudentView.extend({
        events: {
           "click input[name=download]":   "onDownload",
           "change textarea": "onWriting"
        },
        
        initialize: function(options) {
        },

        render: function() {
            return this; 
        },
        
        postRender: function() {
            var $view =  this;
            var $notequantity = $view.$(".cw-noteblock-quantity-stored").val();
            var $original = $view.$("textarea");
            
            var $notetype = $original.prop("class");
            for(var $i = 1; $i < $notequantity; $i++ ){
                ($original.clone()).insertAfter($original);
            }
            this.onWriting();
            if ($view.$(".cw-noteblock-header2-stored").val() != "") {
                var $header2 = $.parseJSON($view.$(".cw-noteblock-header2-stored").val());
                
                if ($notetype == "classic") { 
                    $view.$("textarea").each(function(){
                       var string = $header2.shift();
                        $("<p>"+string+"</p>").insertBefore($(this));
                        $("<br><br>").insertAfter($(this));
                    });
                } else {
                    var string = $header2.shift();
                    $("<p>"+string+"</p>").insertBefore($view.$("textarea").first());
                }
            }
        },
        
        onWriting: function() {
            var $view = this;
            var $data = $view.$("input[name=note-data]");
            var $content = [];
            var $notes = this.$(".post-it");
            if ($notes.length != 0) {
                $.each($notes, function($i){ 
                    $content.push($(this).val());
                });
            } else {
                $content.push(this.$(".classic").val());
            }
            $data.val(JSON.stringify($content));
          
        },
        
        onDownload: function(event) {
            
            helper
                .callHandler(this.model.id, "download", {})
                .then(
                    // success
                    function () {
                    },

                    // error
                    function (error) {
                        console.log(error);
                    })
                .done();
        }
    });
});


