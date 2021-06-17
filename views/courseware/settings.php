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

    <footer>
        <?= Button::create(_cw('Übernehmen'), 'submit', array('title' => _cw('Änderungen übernehmen'))) ?>
    </footer>
</form>
