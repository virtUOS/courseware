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
            if ($view.$(".cw-noteblock-questions-stored").val() != "") {
                var $questions = $.parseJSON($view.$(".cw-noteblock-questions-stored").val());
                
                if ($notetype == "classic") { 
                    $view.$("textarea").each(function(){
                       var string = $questions.shift();
                        $("<p>"+string+"</p>").insertBefore($(this));
                        $("<br><br>").insertAfter($(this));
                    });
                } else {
                    var string = $questions.shift();
                    $("<p>"+string+"</p>").insertBefore($view.$("textarea").first());
                }
            }
        },
        
        onWriting: function() {
            var $view = this;
            var $data = $view.$("input[name=note-data]");
            var $content = [];
            var $notes = this.$(".post-it");
            var $classic = this.$(".classic");
            if ($notes.length != 0) {
                $.each($notes, function($i){ 
                    $content.push($(this).val());
                });
            } else if ($classic.length != 0){
                $.each($classic, function($i){ 
                    $content.push($(this).val());
                });
            }
            $data.val(JSON.stringify($content));
          
        },
        
        onDownload: function(event) {
            var $view = this;
            helper
                .callHandler(this.model.id, "download", {})
                .then(
                    // success
                    function () {
                        $view.$(".message_after_download").delay(2000).show(0);
                    },

                    // error
                    function (error) {
                        console.log(error);
                    })
                .done();
        }
    });
});


