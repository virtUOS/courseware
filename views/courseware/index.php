<?
$body_id = 'mooc-courseware-index';
$ASSETS = $plugin->getPluginURL() . '/assets/';

// TODO: $context missing
echo $courseware_block->render("student", $context);
?>

<script>

 <?
 $block_types = array_map("basename", glob($plugin->getPluginPath() . '/blocks/*'));
 $blocks_url  = current(explode("?", $controller->url_for("blocks")));
 ?>


 var require = {

   config: {

     "assets/js/blocks": {
       block_types: <?= json_encode(studip_utf8decode($block_types)) ?>
     },

     "assets/js/url": {
       blocks_url: <?= json_encode(studip_utf8decode($blocks_url)) ?>
     }
   },

   baseUrl: "<?= $plugin->getPluginURL()?>",

   paths: {
     domReady: "assets/js/domReady",
     backbone: "assets/js/vendor/backbone/backbone-min"
   },

   shim: {
     backbone: {
       exports: 'Backbone'
     }
   },

   deps: ["domReady!", "assets/js/blocks!"],

   callback: function(domReady, blocks) {

     // var courseware = new Courseware({el: document.getElementById('courseware')});
     // blockloader.then(function () { console.log("debug", arguments); }, function () { console.log("debug", arguments); },
     console.log(blocks);
   }
 };
</script>
<script src="<?= $ASSETS ?>js/require.js"></script>
