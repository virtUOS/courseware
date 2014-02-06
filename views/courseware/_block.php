<?
$id = intval($_block->id);
$ui_block = $this->container['block_factory']->makeBlock($_block);
?>
<section id=block-<?= $id ?> class="block <?= htmlReady($_block->type) ?>">
  <h1>Block No. <?= $id ?></h1>
  <?= $this->container['block_renderer']($ui_block, 'student') ?>
</section>
