<?
$open = ($_subchapter->id == $subchapter->id);
?>
<li class="subchapter<?= $open ? ' selected' : '' ?>">
  <span>id:<?= $_subchapter->id ?> name:<?= htmlReady($_subchapter->title) ?></span>
</li>
