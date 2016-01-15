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
            $('#flipbook').turn({
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
                        
                        $("progress").val($current[0]/$allpages*100);
                        
                    }
                }
            });
            
            $(".flipbook-left-control").click(function(){
                    $('#flipbook').turn('previous');
            });
            
            $(".flipbook-right-control").click(function(){
                    $('#flipbook').turn('next');
            });
            
            $('#flipbook').bind('DOMMouseScroll', function(e){
                 if(e.originalEvent.detail > 0) {
                     //scroll down
                     $('#flipbook').turn('next');
                 } else {
                     //scroll up
                     $('#flipbook').turn('previous');
                 }
                 //prevent page fom scrolling
                 return false;
            });
            
            $('#flipbook').bind('mousewheel', function(e){
                if(e.originalEvent.wheelDelta < 0) {
                     //scroll down
                     $('#flipbook').turn('next');
                 } else {
                     //scroll up
                     $('#flipbook').turn('previous');
                 }

                 //prevent page fom scrolling
                return false;
            });
            
            $(window).bind('keydown', function(e){
    
                if (e.keyCode==37)
                    $('#flipbook').turn('previous');
                else if (e.keyCode==39)
                    $('#flipbook').turn('next');
                    
            });
            

        }
    });
});
