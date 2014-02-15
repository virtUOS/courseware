<?
$selected = in_array($_subchapter->id, $active);
?>
<li class="subchapter<?= $selected ? ' selected' : '' ?>">
  <span>id:<?= $_subchapter->id ?> name:<?= htmlReady($_subchapter->title) ?></span>
</li>
