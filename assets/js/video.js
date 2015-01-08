define(['utils'], function (
    Utils
) {
    'use strict';
    
    return {
        init: function() {
            var $iFrame = $('iframe', $('#videobox'));

            $iFrame.attr('src', Utils.getVideoUrl($iFrame.attr('src')));

            jQuery('#preview_video').click(function() {
                jQuery('#videobox').dialog({ 
                    width: '580',
                    height: '400',
                    resizable: false,
                    modal: true,
                    draggable: false,
                    title: 'Vorschauvideo'.toLocaleString()
                }).show();
            });
        }
    }
});
