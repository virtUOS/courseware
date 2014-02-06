<?
$open = ($_chapter->id == $chapter->id);
?>
<li class="chapter<?= $open ? ' selected' : '' ?>">
  <span>id:<?= $_chapter->id ?> name:<?= htmlReady($_chapter->title) ?></span>
  <? if ($open) : ?>
    <ol class=subchapters>
      <?= $this->render_partial_collection('courseware/_subchapter', $_chapter->subchapters) ?>
    </ol>
  <? endif ?>
</li>
