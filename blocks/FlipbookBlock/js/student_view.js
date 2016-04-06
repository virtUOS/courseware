define(['assets/js/student_view', 'assets/js/vendor/turn/turn'],
       function (StudentView) {

    'use strict';

    return StudentView.extend({
        events: {
        },

        initialize: function() {
            var $section = this.$el.closest('section.FlipbookBlock');
            var $sortingButtons = jQuery('button.lower', $section);
            $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
            $sortingButtons.removeClass('no-sorting');
        },

        render: function() {
            return this;
        },

        postRender: function () {
            var $flipbook = this.$el.find('.flipbook');
            
            var $flipbookimg = this.$el.find('.flipbookoverlay').find('.flipbookimgfull').first()[0];
            var $imgheight = $flipbookimg.naturalHeight;
            var $imgwidth = $flipbookimg.naturalWidth;
            var $portrait = $imgheight > $imgwidth;
            if ($portrait) {
                $flipbook.turn({
                    display: 'double',
                    acceleration: true,
                    gradients: !$.isTouch,
                    elevation:50,
                    when: {
                        turned: function(e, page) {
                            
                            var $current = $(this).turn('view');
                            var $allpages = $(this).attr("pages");
                            
                            if ($current[0] == 0) $(".flipbook-current").html($current[1]);
                            else if ($current[1] == 0) $(".flipbook-current").html($current[0]);
                            else $(".flipbook-current").html($current[0] +"/"+ $current[1]);
                            $(".flipbook-allpages").html($allpages);
                            if($allpages != 0) {
                                $("progress").val($current[0]/$allpages*100);
                            }
                        }
                    }
                });
            } else {
                $flipbook.turn({
                    display: 'single',
                    acceleration: true,
                    gradients: !$.isTouch,
                    elevation:50,
                    when: {
                        turned: function(e, page) {
                            
                            var $current = $(this).turn('view');
                            var $allpages = $(this).attr("pages");
                            $(".flipbook-current").html($current[0]);
                      
                            $(".flipbook-allpages").html($allpages);
                            if($allpages != 0) {
                                $("progress").val($current[0]/$allpages*100);
                            }
                        }
                    }
                });
            }
            
            $(".flipbook-left-control").click(function(){
                    $flipbook.turn('previous');
            });
            
            $(".flipbook-right-control").click(function(){
                    $flipbook.turn('next');
            });
            
            $flipbook.bind('DOMMouseScroll', function(e){
                 if(e.originalEvent.detail > 0) {
                     //scroll down
                     $flipbook.turn('next');
                 } else {
                     //scroll up
                     $flipbook.turn('previous');
                 }
                 //prevent page fom scrolling
                 return false;
            });
            
            $flipbook.bind('mousewheel', function(e){
                if(e.originalEvent.wheelDelta < 0) {
                     //scroll down
                     $flipbook.turn('next');
                 } else {
                     //scroll up
                     $flipbook.turn('previous');
                 }

                 //prevent page fom scrolling
                return false;
            });
            
            this.$el.find('.flipbookfullon').on('click', function(e){
                var $height = $(window).height();
                var $width = $(window).width();
                //console.log("height: "+$height+" width: "+$width);
                $(".flipbookoverlay").height("0px").width("0px").show().css({"top": $height/2, "left": $width/2}).animate({
                  height: $height,
                  width : $width,
                  top: 0,
                  left: 0
                }, 600);
               $('.flipbookimgfull').height($height*0.95);
               if ($portrait) {$('.flipbookimgfull-container').height($height*0.95).width((($('.flipbookimgfull').width()*2)+5));}
               else {$('.flipbookimgfull-container').height($height*0.95).width($('.flipbookimgfull').width());}
               var $top = ($height -  $('.flipbookimgfull-container').height()) /2;
               console.log($top);
               $('.flipbookimgfull-container').css({"position": "relative", "top": $top});
               $(".flipbookfulloff").css({"position": "relative", "top": $top});
                
                
                
                
                var $currentimage = $flipbook.turn('view');
                console.log($currentimage);
                
                $.each($currentimage, function($i, $v){
                    if ($v == 0) { $('.flipbookimgfull-container').width($('.flipbookimgfull').width()); return;}
                    console.log($v);
                    if($v < 10){
                         var $bigpicture = $('.flipbookimgfull[image-data="00'+$v+'.jpg"]');
                    }
                    if(($v < 100)&&($v > 9)){
                         var $bigpicture = $('.flipbookimgfull[image-data="0'+$v+'.jpg"]');
                    }
                    $bigpicture.show();
                    
                });
                $('body').css("overflow", "hidden");
            });
            
            function disableFullscreen() 
            {
				$(".flipbookoverlay").hide();
                $(".flipbookimgfull").hide();
                $('body').css("overflow", "auto");
			}
            
            this.$el.find(".flipbookfulloff").on("click", function(e){
                 disableFullscreen();
            });
            
            $(window).bind('keydown', function(e){
				switch (e.keyCode){
                case 37: // cursor left
                    $flipbook.turn('previous');
                    break;
                case 39: // cursor right
                    $flipbook.turn('next');
                    break;
                case 27: // esc
					disableFullscreen();
					break;
                }
                console.log(e.keyCode);
                    
            });

        }
    });
});
