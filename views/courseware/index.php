<? $body_id = 'mooc-courseware-index'; ?>

<?
  $block_types = $plugin->getBlockFactory()->getBlockClasses();
  $blocks_url = PluginEngine::getURL($plugin, array(), "blocks",     true);
  $cid = $container['cid'];
  $courseware_url = PluginEngine::getURL($plugin, array(), "courseware", true);
  $plugin_url = PluginEngine::getURL($plugin, array(), "",           true);
?>

<script>
  var COURSEWARE = {
    config: {
      blocks_url: <?= json_encode(studip_utf8encode($blocks_url)) ?>,
      cid: <?= json_encode(studip_utf8encode($cid)) ?>,
      courseware_url: <?= json_encode(studip_utf8encode($courseware_url)) ?>,
      plugin_url: <?= json_encode(studip_utf8encode($plugin_url)) ?>,
      block_types: <?= json_encode(studip_utf8encode($block_types)) ?>,
      templates: <?= json_encode(studip_utf8encode($templates)) ?>
    }
  };
</script>

<script src="<?= $plugin->getPluginURL() ?>/assets/static/courseware.js" charset="utf-8"></script>
    <? PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css') ?>

<?= $courseware_block->render($view, $context) ?>
