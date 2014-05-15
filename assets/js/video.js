define(['utils'], function (
    Utils
) {
    'use strict';
    
    return {
        init: function() {
            $('#videobox iframe').attr('src', Utils.normalizeLink($('#videobox iframe').attr('src')));

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
