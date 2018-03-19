<?php
foreach ($translatable_texts as $text) {
    $translations[$text] = _cw($text);
}

?>
{
  "<?= strtr($language, "_", "-") ?>": <?= json_encode($translations) ?>
}
