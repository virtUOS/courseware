<? $body_id = 'courseware-index'; ?>
<div style="max-width: 1095px;">
    <article class="studip">
        <header>
            <h1>Informationen</h1>
        </header>
        <section>
            <? if($status !== null):?>
                <?= MessageBox::success('Lerninhalte wurden automatisch übertragen');?>
            <? else: ?>
                <?= MessageBox::warning('Lerninhalte wurden noch nicht automatisch übertragen');?>
            <? endif; ?>
            Seit der Stud.IP Version 5.0 ist Courseware ein fester Systembestandteil. Aufgrund diverser Neuerungen wurden die
            Lerninhalte in die neue Struktur übertragen. Courseware 5 bietet eine Vielzahl von neuen Funktionen
            und verfügt über die neusten Bedienkonzepte in Stud.IP. Unter dem Menüpunkt "Mein Arbeitsplatz" haben nun
            alle Nutzenden die Möglichkeit eigene Lerninhalte zu erstellen und können diese dann mit anderen in Veranstaltungen
            und Studiengruppen teilen.<br>
            Ausführliche Informationen zur neuen Courseware finden Sie in der <a href="https://hilfe.studip.de/help/5.0/de/Basis.Courseware" target="_blank">
            Stud.IP Hilfe</a>.<br>
            <a href="<?= $CoursewareLink ?>" class="button">
                Zur Courseware dieser Veranstaltung
            </a>
        </section>
    </article>
    <? if ($canMigrate): ?>
        <article class="studip">
            <header>
                <h1>Erneute Migration alter Courseware-Inhalte</h1>
            </header>
            <section class="state-idle">
                Sie können diese Funktion verwenden, um bereits automatisch von Stud.IP
                konvertierte Courseware-Inhalte durch eine Neumigration aus der alten
                Courseware zu ersetzen. 
                <br /><b> BITTE BEACHTEN: <br />
                Hierbei werden alle aktuellen
                Courseware-Inhalte in diesem Kurs gelöscht und durch eine Kopie der
                alten Courseware-Inhalte (mit Stand 29.9.2021) ersetzt.</b>
                <br />
                <button class="button" id="migrate">
                    Migration starten
                </a>
            </section>
            <section class="state-loading">
                <div class="loading-indicator">
                    <span class="load-1"></span>
                    <span class="load-2"></span>
                    <span class="load-3"></span>
                </div>
                <p>
                    Courseware-Inhalte werden migriert. Bitte haben Sie Geduld, diese Operation kann einige Minuten dauern.
                </p>
            </section>
            <section class="state-done">
                <?= MessageBox::success('Die erneute Migration der alten Courseware-Inhalte war erfolgreich');?>
                <a href="<?= $CoursewareLink ?>" class="button">
                    Zur Courseware dieser Veranstaltung
                </a>
            </section>
            <section class="state-error">
            <div class="messagebox messagebox_error ">
                <div class="messagebox_buttons">
                    <a class="close" href="#" title="Nachrichtenbox schließen">
                        <span>Nachrichtenbox schließen</span>
                    </a>
                </div>
                    <span class="error-message">Die erneute Migration der alten Courseware-Inhalte ist fehlgeschlagen</span>
            </div>
            </section>
        </article>
    <? endif; ?>
</div>

<script>
    $('.state-loading').hide();
    $('.state-done').hide();
    $('.state-error').hide();
    $('#migrate').on('click', function() {
        $('.state-idle').hide();
        $('.state-loading').show();
        $.ajax({
            url: STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/courseware/courseware/migrate?cid=' + STUDIP.URLHelper.parameters.cid,
            type: 'POST',
            success: response => {
                $('.state-loading').hide();
                if (response.code === 200) {
                    $('.state-done').show();
                } else {
                    $('.state-error .error-message').html(response.error);
                    $('.state-error').show();
                }
            },
            error: response => {
                $('.state-loading').hide();
                $('.state-error').show();
            }
        });
    });

</script>
<style>
    .state-loading p {
        text-align: center;
    }
    .loading-indicator {
        margin: 10px auto;
        width: 38px;
    }
    .loading-indicator span {
        background-color: #CCCCDD;
        border-radius: 50%;
        height: 10px;
        position: relative;
        width: 10px;
        display: inline-block;
    }

    .loading-indicator span.load-1 {
        animation: loading-animation-1 1s linear 20;
    }

    .loading-indicator span.load-2 {
        animation: loading-animation-2 1s linear 20;
    }

    .loading-indicator span.load-3 {
        animation: loading-animation-3 1s linear 20;
    }
</style>
