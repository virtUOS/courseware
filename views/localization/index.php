<?php
foreach ($translatable_texts as $text) {
    $translations[$text] = studip_utf8encode(_cw(studip_utf8decode($text)));
}

?>
{
  "<?= strtr($language, "_", "-") ?>": <?= json_encode($translations) ?>
}
