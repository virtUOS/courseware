<?
$selected = in_array($_chapter->id, $active);
?>
<li class="chapter <?= $selected ? 'selected' : '' ?>">
  <span>id:<?= $_chapter->id ?> name:<?= htmlReady($_chapter->title) ?></span>
  <? if ($open) : ?>
    <ol class=subchapters>
      <?= $this->render_partial_collection('courseware/_subchapter', $_chapter->children) ?>
    </ol>
  <? endif ?>
</li>
