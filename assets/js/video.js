define(['utils'], function (Utils) {
    'use strict';

    return {
        init: function() {
            jQuery('#preview_video').click(function() {
                var $iFrame = $('iframe', $('#videobox'));

                // skip processing if there are not video iframes
                if ($iFrame.length == 0) {
                    return;
                }

                var url = $iFrame.attr('data-url');

                switch (Utils.getVideoType(url)) {
                    case 'youtube':
                        url = Utils.buildYouTubeLink(Utils.getYouTubeId(url), '', '', '', '', true);
                    break;

                  case 'matterhorn':
                        url = Utils.buildMatterhornLink(url);
                    break;
                }

                $iFrame.attr('src', url);

                jQuery('#videobox').dialog({
                    width: '580',
                    height: '400',
                    resizable: false,
                    modal: true,
                    draggable: false,
                    title: 'Vorschauvideo'.toLocaleString(),
                    close: function() {
                        $('#videobox iframe').attr('src', 'about:blank');
                    }
                }).show();
            });
        }
    }
});
