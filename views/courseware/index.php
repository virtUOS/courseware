<? $body_id = 'courseware-index'; ?>
<div style="max-width: 1095px;">
    <div id="cw-messagebox-wrapper">
        <? if($status !== null):?>
            <?= MessageBox::success('Lerninhalte wurden übertragen');?>
        <? else: ?>
            <?= MessageBox::warning('Lerninhalte wurden noch nicht übertragen');?>
        <? endif; ?>
    </div>
    <article class="studip" id="cw-info">
        <header>
            <h1>Informationen</h1>
        </header>
        <section>
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
                <? if($status !== null):?>
                    <h1>Erneute Migration alter Courseware-Inhalte</h1>
                <? else: ?>
                    <h1>Manuelle Migration alter Courseware-Inhalte</h1>
                <? endif; ?>
            </header>
            <section class="state-idle">
                <? if($status !== null):?>
                    <p>
                        Sie können diese Funktion verwenden, um bereits automatisch von Stud.IP
                        konvertierte Courseware-Inhalte durch eine Neumigration aus der alten
                        Courseware zu ersetzen.
                    </p>
                    <h4>BITTE BEACHTEN:</h4>
                    <p style="font-weight: 700"> 
                        Hierbei werden alle aktuellen
                        Courseware-Inhalte in diesem Kurs gelöscht und durch eine Kopie der
                        alten Courseware-Inhalte ersetzt.
                    </p>
                <? else: ?>
                    <p>
                        Sie können diese Funktion verwenden, um Courseware-Inhalte des Courseware-Plugins
                        in die Courseware von Stud.IP 5 zu übertragen. Durch die neue Struktur von
                        Courseware werden Inhalte ggf. anders dargestellt. 
                    </p>
                <? endif; ?>
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
                <?= MessageBox::success('Die  Migration der alten Courseware-Inhalte war erfolgreich');?>
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
                <span class="error-message">Die Migration der alten Courseware-Inhalte ist fehlgeschlagen</span>
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
        STUDIP.Dialog.confirm('Möchten Sie die Migration wirklich starten?').done(function() {
            $('#cw-messagebox-wrapper').hide();
            $('#cw-info').hide();
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
