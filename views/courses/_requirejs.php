<?php
/** @var \Mooc $plugin */
?>
<script>

'use strict';

<?
$block_types = $plugin->getBlockFactory()->getBlockClasses();
$plugin_url = PluginEngine::getURL($plugin, array(), '', true);
$blocks_url  = current(explode("?", $controller->url_for("blocks")));
?>

var require = {
    baseUrl: "<?= $plugin->getPluginURL()?>",

    paths: {
        domReady: "assets/js/domReady",
        video:    "assets/js/video",
        utils:    "assets/js/utils"
    },

    deps: ['domReady!'],

    callback: function() {
        require(['video'], function (Video) {
            Video.init();
        });
    }
};
</script>
<script src="<?= $plugin->getPluginURL() . '/assets/' ?>js/vendor/requirejs.v2.1.11/require-min.js"></script>
