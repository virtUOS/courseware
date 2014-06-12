<script>

 'use strict';

 <?
  $block_types = $container['block_factory']->getBlockClasses();

  $blocks_url = current(explode("?", $controller->url_for("blocks")));
  $courseware_url = current(explode("?", $controller->url_for("courseware")));
  $plugin_url = PluginEngine::getURL($plugin, array(), '', true);
 ?>

 var require = {

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

   paths: {
     domReady: "assets/js/domReady",
     jquery:   "assets/js/jquery_compat",
     backbone: "assets/js/vendor/backbone/backbone-min",
     argjs:    "assets/js/vendor/arg.js/arg.js.v1.1",
     mustache: "assets/js/vendor/mustache.js-0.8.1/mustache",
     q:        "assets/js/vendor/q.v1/q.min",
     scrollTo: "assets/js/vendor/jquery.scrollTo/jquery.scrollTo.min",
     utils:    "assets/js/utils",
     dateFormat: "assets/js/vendor/date.format/date.format"
   },

   shim: {
     backbone: {
       exports: 'Backbone'
     },
     argjs: {
       exports: 'Arg',
     },
     scrollTo: {
       deps: ['jquery']
     },
   },
   deps: _.map(<?= json_encode(studip_utf8encode($block_types)) ?>, function (type) {
     return ['blocks/', type, "/js/", type].join('');
   })
 };
</script>
<script data-main="assets/js/main-courseware"
        src="<?= $plugin->getPluginURL() . '/assets/' ?>js/vendor/requirejs.v2.1.11/require-min.js"></script>
