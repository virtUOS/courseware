<?php
/** @var \Mooc $plugin */
?>
<?
  $block_types = $plugin->getBlockFactory()->getBlockClasses();

  $blocks_url     = current(explode("?", $controller->url_for("blocks")));
  $courseware_url = current(explode("?", $controller->url_for("courseware")));
  $plugin_url     = PluginEngine::getURL($plugin, array(), '', true);

  $src_dir = $plugin->getPluginURL() . '/assets/js/';
 ?>

<script src="<?= $src_dir ?>vendor/requirejs.v2.1.11/require-min.js"></script>
<script src="<?= $src_dir ?>config.js"></script>

<script>


 (function () {
   'use strict';

   require.config({

     config: {

       "assets/js/block_loader": {
         block_types: <?= json_encode(studip_utf8encode($block_types)) ?>
                          },

       "assets/js/url": {
         blocks_url:     <?= json_encode(studip_utf8encode($blocks_url)) ?>,
         courseware_url: <?= json_encode(studip_utf8encode($courseware_url)) ?>,
         plugin_url:     <?= json_encode(studip_utf8encode($plugin_url)) ?>
                             },

       "assets/js/templates": {
         templates: <?= json_encode(studip_utf8encode($templates)) ?>
                        }
     },

     baseUrl: "<?= $plugin->getPluginURL()?>",

     deps: _.map(<?= json_encode(studip_utf8encode($block_types)) ?>, function (type) {
       return ['blocks/', type, "/js/", type].join('');
     })
   });

   require(['assets/js/main-courseware']);

 }());
</script>
