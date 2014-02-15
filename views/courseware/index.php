<?
$body_id = 'mooc-courseware-index';
$ASSETS = $plugin->getPluginURL() . '/assets/';
?>

<section id=courseware>

  <h1> Courseware: <?= htmlReady($courseware->title) ?></h1>

  <ol class=chapters>
    <?= $this->render_partial_collection('courseware/_chapter', $courseware->children) ?>
  </ol>

  <ol class=sections>
    <?= $this->render_partial_collection('courseware/_section', $section->parent->children) ?>
  </ol>

  <section class=content>
    <p>Diese Section hat <?= count($section->children) ?> Block/s.</p>

    <?= $this->render_partial_collection('courseware/_block', $section->children) ?>

  </section>

</section>


<script>

 <?
 $block_types = array_map("basename", glob($plugin->getPluginPath() . '/blocks/*'));
 $block_deps  = array_map(function ($type) { return "\"blocks/$type/js/$type\""; }, $block_types);
 $blocks_url  = current(explode("?", $controller->url_for("blocks")));
 ?>


 var require = {

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

   deps: ["domReady!", "assets/js/courseware", <?= join(",", $block_deps) ?>],

   callback: function(domReady, Courseware) {
     var courseware = new Courseware({el: document.getElementById('courseware')});
   },

   config: {
     "assets/js/courseware": {
       block_types: <?= json_encode(studip_utf8decode($block_types)) ?>
     },

     "assets/js/url": {
       blocks_url: <?= json_encode(studip_utf8decode($blocks_url)) ?>
     }
   }
 };
</script>
<script src="<?= $ASSETS ?>js/require.js"></script>
