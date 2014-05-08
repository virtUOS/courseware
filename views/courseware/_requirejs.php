<script>

 'use strict';

 <?
  $block_types = $container['block_factory']->getBlockClasses();
  $plugin_url = PluginEngine::getURL($plugin, array(), '', true);
  $blocks_url  = current(explode("?", $controller->url_for("blocks")));
 ?>

 var require = {

   config: {

     "assets/js/block_loader": {
       block_types: <?= json_encode(studip_utf8encode($block_types)) ?>
     },

     "assets/js/url": {
       plugin_url: <?= json_encode(studip_utf8encode($plugin_url)) ?>,
       blocks_url: <?= json_encode(studip_utf8encode($blocks_url)) ?>
     },

     "assets/js/templates": {
       templates: <?= json_encode(studip_utf8encode($templates)) ?>
     }
   },

   baseUrl: "<?= $plugin->getPluginURL()?>",

   paths: {
     domReady: "assets/js/domReady",
     backbone: "assets/js/vendor/backbone/backbone-min",
     argjs:    "assets/js/vendor/arg.js/arg.js.v1.1",
     mustache: "assets/js/vendor/mustache.js-0.8.1/mustache",
     q:        "assets/js/vendor/q.v1/q.min"
   },

   shim: {
     backbone: {
       exports: 'Backbone'
     },
     argjs: {
       exports: 'Arg',
     }
   },

   deps: ['domReady!', 'backbone', 'assets/js/block_loader!', 'assets/js/block_types', 'assets/js/block_model'],

   callback: function(domReady, Backbone, block_loader, blocks, BlockModel) {

     Backbone.history.start({
       push_state: true,
       silent: true,
       root: <?= json_encode(studip_utf8encode(current(explode('?',$controller->url_for('courseware'))))) ?>
     });

     blocks.reset(block_loader);

     var view = blocks.get("Courseware").createView("student", {
       el: jQuery("#courseware"),
       model: new BlockModel({
         id: <?= (int) $courseware->id ?>,
         type: "Courseware"
       })
     });
   }
 };
</script>
<script src="<?= $plugin->getPluginURL() . '/assets/' ?>js/vendor/requirejs.v2.1.11/require-min.js"></script>
