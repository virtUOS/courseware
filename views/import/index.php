<h2><?=_cw("Datei hochladen")?></h2>
<?php
/** @var string[] $errors */
/** @var string[] $warnings */

if (count($errors) > 0) {
    echo"<p><b>"._cw("Es sind Fehler aufgetreten:")."</b></p>";
    echo '<ul>';
    foreach ($errors as $error):
        echo '<li>'.htmlReady($error).'</li>';
    endforeach;
    echo '</ul>';
}

if (count($warnings) > 0) {
    echo"<p><b>"._cw("Warnung!")."</b></p>";
    echo '<ul>';
    foreach ($warnings as $warning):
        echo '<li>'.htmlReady($warning).'</li>';
    endforeach;
    echo '</ul><br><br>';
    echo"<p><b>"._cw("Bitte überprüfen Sie den Inhalt Ihrer Courseware und den Daten in Ihrer Importdatei.")."</b></p>";
}
?>
<? if (empty($warnings)&& empty($errors)): ?>
    <p>&nbsp;</p>
    <p><?= _cw("Laden Sie eine Datei hoch, die Sie zuvor aus einer Courseware exportiert haben.")?></p>

    <p>&nbsp;</p>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="subcmd" value="upload">
        <input type="file" name="import_file" accept=".zip">

        <div>
            <?php
            echo Studip\Button::createAccept();
            echo Studip\LinkButton::createCancel(_cw('Abbrechen'), PluginEngine::getURL($this->plugin, array(), 'courseware'));
            ?>
        </div>
    </form>
<? endif ?>
