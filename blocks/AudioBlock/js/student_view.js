define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    
    'use strict';
    
    return StudentView.extend({
        events: {
            "click button[name=play]":   "playAudioFile",
            "ended .cw-audio-player":    "playAudioFileEnd"
        },
        
        initialize: function(options) {
        },

        render: function() {
            return this; 
        },
        
        postRender: function() {
            var $view =  this;
            var $player = $view.$(".cw-audio-player");
            
            $player.find("source").each(function(){
                var $source = $(this).prop("src");
                if ($source.indexOf("ogg") > -1) {
                    $(this).prop("type", "audio/ogg")
                }
                if ($source.indexOf("wav") > -1) {
                    $(this).prop("type", "audio/wav")
                }
                // default: type="audio/mpeg"
            });
            
        },
                
        playAudioFile: function() {
             var $view =  this;
             var $player = $view.$(".cw-audio-player")[0];
             var $playbutton = $view.$(".cw-audio-playbutton");
             if (!$playbutton.hasClass("cw-audio-playbutton-playing")) {
                 $playbutton.addClass('cw-audio-playbutton-playing');
                 $player.load();
                 $player.play();
               
                 return;
               } else {
                 $playbutton.removeClass('cw-audio-playbutton-playing');
                 $player.pause();
               }
             helper
                .callHandler(this.model.id, "play", {})
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
        
        playAudioFileEnd: function(){
            var $view =  this;
            var $playbutton = $view.$(".cw-audio-playbutton");
            $playbutton.removeClass('cw-audio-playbutton-playing');
        }
    });
});


