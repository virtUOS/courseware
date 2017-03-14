define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    
    'use strict';
    
    return StudentView.extend({
        events: {
            "click button[name=play]":       "playAudioFile",
            "click button[name=pause]":      "pauseAudioFile",
            "ended .cw-audio-player":        "playAudioFileEnd"
        },
        
        initialize: function(options) {
            
        },

        render: function() {
            return this; 
        },
        
        postRender: function() {
            var $view =  this;
            var $player = $view.$(".cw-audio-player");
            if ($view.$(".cw-audio-playbutton").attr("played") == "1") {
                    $player.remove();
                    $view.$(".cw-audio-playbutton").remove();
                    $view.$(".cw-audio-played-message").show();
            } else {
                $view.$(".cw-audio-playbutton").show();
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
            }
            var music = $player[0];
            music.addEventListener("ended", this.playAudioFileEnd, false);
            
        },
                
        playAudioFile: function() {
             var $view =  this;
             var $player = $view.$(".cw-audio-player")[0];
             var $playbutton = $view.$(".cw-audio-playbutton");
             var $pausebutton = $view.$(".cw-audio-pausebutton");
             if (!$playbutton.hasClass("cw-audio-playbutton-playing")) {
                 $playbutton.addClass('cw-audio-playbutton-playing');
                 $player.load();
                 $player.play();
                 $pausebutton.show();
                 return;
                 
               } else {
                 $playbutton.remove();
                 $pausebutton.remove();
                 $player.pause();
                 $view.$(".cw-audio-played-message").show();
               }
             helper
                .callHandler(this.model.id, "play", {})
                .then(
                    // success
                    function () {
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();

        },
        
        pauseAudioFile: function() {
            console.log("pause");
             var $view =  this;
             var $player = $view.$(".cw-audio-player")[0];
             var $pausebutton = $view.$(".cw-audio-pausebutton");
             if (!$pausebutton.hasClass("cw-audio-pausebutton-pausing")) {
                $pausebutton.addClass("cw-audio-pausebutton-pausing");
                $player.pause();
            }
            else {
                $player.play();
                $pausebutton.removeClass("cw-audio-pausebutton-pausing");
            }
        },
        
        playAudioFileEnd: function(){
            var $blockid = $(this).parent().parent().attr("data-blockid");
            var $playbutton = $(this).parent().find(".cw-audio-playbutton");
            $playbutton.remove();
            $(this).parent().find(".cw-audio-played-message").show();
            helper
                .callHandler($blockid, "play", {})
                .then(
                    // success
                    function () {
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


