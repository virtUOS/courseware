
<div style="max-width: 1095px;">
    <div id="cw-messagebox-wrapper">
        <?
            /** @var string[] $errors */
            /** @var string[] $warnings */

            if (count($errors) > 0) {
                echo MessageBox::error(_cw("Es sind Fehler aufgetreten"), $errors );
            }

            if (count($warnings) > 0) {
                echo MessageBox::warning(_cw("Warnung"), array_merge($warnings, [_cw("Bitte überprüfen Sie den Inhalt Ihrer Courseware und den Daten in Ihrer Importdatei.")]) );
            }

            if ($success) {
                echo MessageBox::success(_cw("Inhalte wurden erfolgreich importiert."));
            }
        ?>
    </div>
    <article class="studip" id="cw-info">
        <header>
            <h1><?=_cw("Datei hochladen")?></h1>
        </header>
        <section>
            <p><?= _cw("Laden Sie eine Datei hoch, die Sie zuvor aus einer Courseware exportiert haben.")?></p>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="subcmd" value="upload">
                <input type="file" name="import_file" accept=".zip">

                <div>
                    <?php
                    echo Studip\Button::createAccept(_cw('Importieren'));
                    echo Studip\LinkButton::createCancel(_cw('Abbrechen'), PluginEngine::getURL($this->plugin, array(), 'courseware'));
                    ?>
                </div>
            </form>
        </section>
    </article>

</div>
