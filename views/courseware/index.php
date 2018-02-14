<? $body_id = 'courseware-index'; ?>

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
      blocks_url: <?= json_encode($blocks_url) ?>,
      cid: <?= json_encode($cid) ?>,
      courseware_url: <?= json_encode($courseware_url) ?>,
      plugin_url: <?= json_encode($plugin_url) ?>,
      block_types: <?= json_encode($block_types) ?>,
      templates: <?= json_encode($templates) ?>,
      nobody: <?= json_encode($container['current_user']->isNobody()) ?>,
      user_may_author: <?= json_encode($container['current_user']->canUpdate($courseware_block)) ?>,
    }
  };
</script>

<script src="<?= $plugin->getPluginURL() ?>/assets/static/courseware.js" charset="utf-8"></script>
    <? PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css') ?>

<?= $courseware_block->render($view, $context) ?>
