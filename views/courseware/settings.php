<?
use Studip\Button;
use Mooc\UI\Courseware\Courseware;

if ($flash['success']) {
    PageLayout::postMessage(MessageBox::success($flash['success']));
}

?>

<form class="default collapsable" method="post" action="<?= $controller->url_for('courseware/settings') ?>">
    <?= CSRFProtection::tokenTag() ?>

    <fieldset>
        <legend><?= _cw('Allgemeines') ?></legend>
        <label>
                <?= _cw('Titel der Courseware') ?>
                <dfn id="courseware-title-description">
                    <?= _cw('Der Titel der Courseware erscheint als Beschriftung des Courseware-Reiters. Sie können den Reiter also z.B. auch "Online-Skript", "Lernmodul" o.ä. nennen.'); ?>
                </dfn>
                <input id="courseware-title" type="text" name="courseware[title]" value="<?= htmlReady($courseware_block->title) ?>" aria-describedby="courseware-progression-description">
        </label>

        <label>
            <?= _cw('Art der Kapitelabfolge') ?>
            <dfn id="courseware-progression-description">
                <?= _cw('Bei freier Kapitelabfolge können alle sichtbaren Kapitel in beliebiger Reihenfolge ausgewählt werden. Bei sequentieller Abfolge müssen alle vorangehenden Unterkapitel erfolgreich abgeschlossen sein, damit ein Unterkapitel ausgewählt und angezeigt werden kann.'); ?>
            </dfn>
            <? $progression_type = $courseware_block->progression; ?>
            <select 
                name="courseware[progression]"
                id="courseware-progression"
                aria-describedby="courseware-progression-description"
                class="size-s"
            >
                <option value="free"<?= $courseware_block->progression === Courseware::PROGRESSION_FREE ? ' selected' : '' ?>> <?= _cw("frei") ?> </option>
                <option value="seq"<?= $courseware_block->progression === Courseware::PROGRESSION_SEQ  ? ' selected' : '' ?>>  <?= _cw("sequentiell") ?> </option>
            </select>
        </label>

        <?= $this->render_partial('courseware/_settings_editing_permission') ?>


        <label>
            <?= _cw('Dritte Navigationsebene anzeigen') ?>
            <dfn id="courseware-section-navigation-description">
                <?= _cw('Wählen Sie hier aus wie die dritte Navigationsebene dargestellt werden soll.'); ?>
            </dfn>
            <select name="courseware[section_navigation]" id="courseware-section-navigation">
                <option value="default"  <?= (!$courseware_block->getSectionsAsChapters() && $courseware_block->getShowSectionNav()) ? "selected" : "" ?> ><?= _cw("Über dem Seiteninhalt horizontal anzeigen") ?></option>
                <option value="chapter" <?= $courseware_block->getSectionsAsChapters() ? "selected" : "" ?>><?= _cw("Links in der Kapitelnavigation anzeigen") ?></option>
                <option value="hide" <?= $courseware_block->getShowSectionNav() ? "" : "selected" ?> ><?= _cw("Nicht anzeigen") ?></option>
            </select>
        </label>

        <label>
            <?= _cw('Scrollytelling aktivieren') ?>
            <dfn id="courseware-scrollytelling-description">
                    <?= _cw('Wenn Sie diesen Schalter aktivieren, wird die Courseware in ein Scrollytelling verwandelt.'); ?>
            </dfn>
            <select id="courseware-scrollytelling"
                name="courseware[scrollytelling]"
                class="size-s"
            >
                <? $scrollytelling = $courseware_block->getScrollytelling() ?>
                <option value="1" <? if ($scrollytelling): ?>selected<? endif ?>><?= _cw('Ja')?></option>
                <option value="0" <? if (!$scrollytelling): ?>selected<? endif ?>><?= _cw('Nein')?></option>
            </select>
        </label>
    </fieldset>

    <fieldset>
        <legend><?= _cw('Blockeinstellungen') ?></legend>
        <?= $this->render_partial('courseware/_settings_discussionblock') ?>
    </fieldset>

    <fieldset>
        <legend><?= _cw('Selbsttests') ?></legend>
        <label>
            <?= _cw('Anzahl Versuche: Quiz (Selbsttest)') ?>
            <dfn id="courseware-max-tries-description">
                <?= _cw('Die Anzahl der Versuche, die ein Student beim Lösen von Aufgaben in einem Quiz vom Type Selbsttest hat, bevor die Lösung der Aufgabe angezeigt wird.'); ?>
            </dfn>
            <input id="max-tries" type="number" min="0" name="courseware[max-tries]" value="<?= htmlReady($courseware_block->max_tries) ?>" aria-describedby="courseware-max-tries-description">
            <?= _cw('Unbegrenzt') ?>
            <input id="max-tries-infinity" type="checkbox" name="courseware[max-tries-infinity]" aria-describedby="courseware-max-tries-description">
            <? if ($courseware_block->max_tries === -1): ?>
                <script>
                    document.getElementById('max-tries-infinity').checked = true;
                    document.getElementById('max-tries').value = 0;
                    document.getElementById('max-tries').disabled = true;
                </script>
            <? endif ?>
            <script>
                document.getElementById('max-tries-infinity').onchange = function() {
                    document.getElementById('max-tries').disabled = this.checked;
                }
            </script>
        </label>
        <label>
            <?= _cw('Anzahl Versuche: Interactive Video') ?>
            <dfn id="courseware-max-tries-iav-description">
                <?= _cw('Die Anzahl der Versuche, die ein Student beim Lösen von Aufgaben in einem interaktiven Video hat, bevor die Lösung der Aufgabe angezeigt wird.'); ?>
            </dfn>
            <input id="max-tries-iav" type="number" min="0" name="courseware[max-tries-iav]" value="<?= htmlReady($courseware_block->max_tries_iav) ?>" aria-describedby="courseware-max-tries-description">
            <span>
                <?= _cw('Unbegrenzt') ?>
                <input id="max-tries-iav-infinity" type="checkbox" name="courseware[max-tries-iav-infinity]" aria-describedby="courseware-max-tries-iav-description">
                <? if ($courseware_block->max_tries_iav === -1): ?>
                    <script>
                        document.getElementById('max-tries-iav-infinity').checked = true;
                        document.getElementById('max-tries-iav').value = 0;
                        document.getElementById('max-tries-iav').disabled = true;
                    </script>
                <? endif ?>
                <script>
                    document.getElementById('max-tries-iav-infinity').onchange = function() {
                        document.getElementById('max-tries-iav').disabled = this.checked;
                    }
                </script>
            </span>
        </label>
        <label></label>
    </fieldset>

    <fieldset>
        <legend>
            <?= _cw('Zertifikat') ?>
        </legend>
        <label>
            <?= _cw('Zertifikat erzeugen') ?>
            <dfn>
                <?= _cw('Wenn der Fortschritt eines Nutzenden die Zertifikatsgrenze erreicht, wird ein Teilnahme-Zertifikat per E-Mail versand.'); ?>
            </dfn>
            <select id="courseware-certificate"
                name="courseware[certificate]"
                class="size-s"
            >
                <? $certificate = $courseware_block->getCertificate() ?>
                <option value="1" <? if ($certificate): ?>selected<? endif ?>><?= _cw('Ja')?></option>
                <option value="0" <? if (!$certificate): ?>selected<? endif ?>><?= _cw('Nein')?></option>
            </select>
        </label>
        <label>
            <?= _cw('Zertifikat Grenze') ?>
            <dfn>
                <?= _cw('Setzt Grenze in Prozent ab der ein Zertifikat versendet werden soll.'); ?>
            </dfn>
            <input 
                type="number"
                min=0
                max=100
                id="courseware-certificate-limit"
                name="courseware[certificate_limit]"
                class="s"
                value="<?= $courseware_block->getCertificateLimit()?>"
            />
        </label>
        <label>
            <?= _cw('Zertifikat Hintergrundbild') ?>
            <dfn>
                <?= _cw('Wählen Sie hier den Hintergrund für das Zertifikat aus.')?><br>
                <?= _cw('Bitte legen Sie hierfür ein Bild, in einen unsichtbaren Dateiordner dieser Veranstaltung, ab.'); ?>
            </dfn>
            <?$image_id = $courseware_block->getCertificateImageId()?>
            <select
                id="courseware-certificate-image-id"
                name="courseware[certificate_image_id]"
            >
                <option value="" <? if($image_id == ''):?> selected <? endif; ?> ><?= _cw('Keine Datei ausgewählt')?></option>
                <? foreach ($files as $file): ?>
                    <option value="<?= $file['id']?>" <? if($image_id == $file['id']):?> selected <? endif; ?>><?= $file['name']?></option>
                <? endforeach; ?>
            </select>
        </label>
    </fieldset>

    <fieldset>
        <legend>
            <?= _cw('Erinnern') ?>
        </legend>
        <label>
            <?= _cw('Erinnerung senden') ?>
            <dfn>
                <?= _cw('Nutzende erhalten in gewählten Zeitinervallen eine Erinnerungs-E-Mail.'); ?>
            </dfn>
            <select id="courseware-reminder"
                name="courseware[reminder]"
                class="size-s"
            >
                <? $reminder = $courseware_block->getReminder() ?>
                <option value="1" <? if ($reminder): ?>selected<? endif ?>><?= _cw('Ja')?></option>
                <option value="0" <? if (!$reminder): ?>selected<? endif ?>><?= _cw('Nein')?></option>
            </select>
        </label>
        <label>
            <?= _cw('Intervall') ?>
            <dfn>
                <?= _cw('Gibt an in welchem zeitlichen Abstand eine Erinnerung versand werden soll.'); ?>
            </dfn>
            <select id="courseware-reminder-interval"
                name="courseware[reminder_interval]"
                class="size-s"
            >
                <? $reminder_interval = $courseware_block->getReminderInterval() ?>
                <option value="0" <? if ($reminder_interval == '0'): ?>selected<? endif ?>><?= _cw('wöchentlich')?></option>
                <option value="1" <? if ($reminder_interval == '1'): ?>selected<? endif ?>><?= _cw('14-tägig')?></option>
                <option value="2" <? if ($reminder_interval == '2'): ?>selected<? endif ?>><?= _cw('monatlich')?></option>
                <option value="3" <? if ($reminder_interval == '3'): ?>selected<? endif ?>><?= _cw('vierteljährlich')?></option>
                <option value="4" <? if ($reminder_interval == '4'): ?>selected<? endif ?>><?= _cw('halbjährlich')?></option>
                <option value="5" <? if ($reminder_interval == '5'): ?>selected<? endif ?>><?= _cw('jährlich')?></option>
            </select>
        </label>
        <label>
            <?= _cw('Startdatum') ?>
            <dfn>
                <?= _cw('An diesem Datum wird zum ersten Mal eine Nachricht verschickt.'); ?>
            </dfn>
            <input 
                value="<?= $courseware_block->getReminderStartDate() ?>"
                type="text"
                name="courseware[reminder_start_date]"
                id="courseware-reminder-start-date"
                class="size-s" 
                data-date-picker='{"<":"#courseware-reminder-end-date"}'/>
        </label>
        <label>
            <?= _cw('Enddatum') ?>
            <dfn>
                <?= _cw('An diesem Datum wird zum letzten Mal eine Nachricht verschickt.'); ?>
            </dfn>
            <input
                value="<?= $courseware_block->getReminderEndDate() ?>"
                type="text"
                name="courseware[reminder_end_date]"
                id="courseware-reminder-end-date"
                class="size-s"
                data-date-picker='{">":"#courseware-reminder-start-date"}'/>
        </label>
        <label>
            <?= _cw('Nachrichtentext') ?>
            <dfn>
                <?= _cw('Erinnerungs-E-Mail Nachrichtentext'); ?>
            </dfn>
            <textarea name="courseware[reminder_message]"><?= $courseware_block->getReminderMessage(); ?></textarea>
        </label>
    </fieldset>

    <fieldset>
        <legend>
            <?= _cw('Fortschritt automatisch zurücksetzen') ?>
        </legend>
        <label>
            <?= _cw('Fortschritt zurücksetzen') ?>
            <dfn>
                <?= _cw('Nutzende erhalten in gewählten Zeitinervallen eine Aufforderung den Inhalt erneut zu bearbeiten. Der Fortschritt zurückgesetzt.'); ?>
            </dfn>
            <select id="courseware-reseter"
                name="courseware[reseter]"
                class="size-s"
            >
                <? $reseter = $courseware_block->getReseter() ?>
                <option value="1" <? if ($reseter): ?>selected<? endif ?>><?= _cw('Ja')?></option>
                <option value="0" <? if (!$reseter): ?>selected<? endif ?>><?= _cw('Nein')?></option>
            </select>
        </label>
        <label>
            <?= _cw('Intervall') ?>
            <dfn>
                <?= _cw('Gibt an in welchem zeitlichen Abstand der Fortschritt zurückgesetzt werden soll.'); ?>
            </dfn>
            <select id="courseware-reseter-interval"
                name="courseware[reseter_interval]"
                class="size-s"
            >
                <? $reseter_interval = $courseware_block->getReseterInterval() ?>
                <option value="0" <? if ($reseter_interval == '0'): ?>selected<? endif ?>><?= _cw('wöchentlich')?></option>
                <option value="1" <? if ($reseter_interval == '1'): ?>selected<? endif ?>><?= _cw('14-tägig')?></option>
                <option value="2" <? if ($reseter_interval == '2'): ?>selected<? endif ?>><?= _cw('monatlich')?></option>
                <option value="3" <? if ($reseter_interval == '3'): ?>selected<? endif ?>><?= _cw('vierteljährlich')?></option>
                <option value="4" <? if ($reseter_interval == '4'): ?>selected<? endif ?>><?= _cw('halbjährlich')?></option>
                <option value="5" <? if ($reseter_interval == '5'): ?>selected<? endif ?>><?= _cw('jährlich')?></option>
            </select>
        </label>
        <label>
            <?= _cw('Startdatum') ?>
            <dfn>
                <?= _cw('An diesem Datum wird zum ersten Mal der Fortschritt zurückgesetzt.'); ?>
            </dfn>
            <input 
                value="<?= $courseware_block->getReseterStartDate() ?>"
                type="text"
                name="courseware[reseter_start_date]"
                id="courseware-reseter-start-date"
                class="size-s" 
                data-date-picker='{"<":"#courseware-reseter-end-date"}'/>
        </label>
        <label>
            <?= _cw('Enddatum') ?>
            <dfn>
                <?= _cw('An diesem Datum wird zum letzten Mal der Fortschritt zurückgesetzt.'); ?>
            </dfn>
            <input
                value="<?= $courseware_block->getReseterEndDate() ?>"
                type="text"
                name="courseware[reseter_end_date]"
                id="courseware-reseter-end-date"
                class="size-s"
                data-date-picker='{">":"#courseware-reseter-start-date"}'/>
        </label>
        <label>
            <?= _cw('Nachrichtentext') ?>
            <dfn>
                <?= _cw('Zurücksetzungs-E-Mail Nachrichtentext'); ?>
            </dfn>
            <textarea name="courseware[reseter_message]"><?= $courseware_block->getReseterMessage(); ?></textarea>
        </label>
    </fieldset>

    <footer>
        <?= Button::create(_cw('Übernehmen'), 'submit', array('title' => _cw('Änderungen übernehmen'))) ?>
    </footer>
</form>
