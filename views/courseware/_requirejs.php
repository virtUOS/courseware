<script>

 <?
 $block_types = array_map("basename", glob($plugin->getPluginPath() . '/blocks/*'));
 $blocks_url  = current(explode("?", $controller->url_for("blocks")));
 ?>

 var require = {

   config: {

     "assets/js/block_loader": {
       block_types: <?= json_encode(studip_utf8decode($block_types)) ?>
     },

     "assets/js/url": {
       blocks_url: <?= json_encode(studip_utf8decode($blocks_url)) ?>,
       base_view: "<?= $view ?>"
     },

     "assets/js/templates": {
       templates: <?= json_encode(studip_utf8decode($templates)) ?>
     }
   },

   baseUrl: "<?= $plugin->getPluginURL()?>",

   paths: {
     domReady: "assets/js/domReady",
     block:    "assets/js/block_loader",
     backbone: "assets/js/vendor/backbone/backbone-min",
     argjs:    "assets/js/vendor/arg.js/arg.js.v1.1"
   },

   shim: {
     backbone: {
       exports: 'Backbone'
     },
     argjs: {
       exports: 'Arg',
     }
   },

   deps: ['domReady!', 'assets/js/block_loader!', 'assets/js/block_types'],

   callback: function(domReady, block_loader, blocks) {
     blocks.reset(block_loader);
     var View = blocks.get("Courseware").student;
     new View({ el: $("#courseware") });
   }
 };
</script>
<script src="<?= $plugin->getPluginURL() . '/assets/' ?>js/vendor/requirejs.v2.1.11/require-min.js"></script>
