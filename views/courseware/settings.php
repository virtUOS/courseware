<?
use Studip\Button;
use Mooc\UI\Courseware\Courseware;

if ($flash['success']) {
    PageLayout::postMessage(MessageBox::success($flash['success']));
}

?>

<form method="post" action="<?= $controller->url_for('courseware/settings') ?>">
    <?= CSRFProtection::tokenTag() ?>

    <table id="main_content" class="default">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>

        <caption>
            <?= _cw('Courseware-Einstellungen') ?>
        </caption>

        <tbody>
            <tr>
                <th colspan="2"><?= _cw('Allgemeines') ?></th>
            </tr>
            <tr>
                <td>
                    <label for="courseware-title">
                        <?= _cw('Titel der Courseware') ?><br>
                        <dfn id="courseware-title-description">
                            <?= _cw('Der Titel der Courseware erscheint als Beschriftung des Courseware-Reiters. Sie können den Reiter also z.B. auch "Online-Skript", "Lernmodul" o.ä. nennen.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input id="courseware-title" type="text" name="courseware[title]" value="<?= htmlReady($courseware_block->title) ?>" aria-describedby="courseware-progression-description">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="courseware-progression">
                        <?= _cw('Art der Kapitelabfolge') ?><br>
                        <dfn id="courseware-progression-description">
                            <?= _cw('Bei freier Kapitelabfolge können alle sichtbaren Kapitel in beliebiger Reihenfolge ausgewählt werden. Bei sequentieller Abfolge müssen alle vorangehenden Unterkapitel erfolgreich abgeschlossen sein, damit ein Unterkapitel ausgewählt und angezeigt werden kann.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <? $progression_type = $courseware_block->progression; ?>
                    <select name="courseware[progression]" id="courseware-progression" aria-describedby="courseware-progression-description">
                        <option value="free"<?= $courseware_block->progression === Courseware::PROGRESSION_FREE ? ' selected' : '' ?>> <?= _cw("frei") ?> </option>
                        <option value="seq"<?= $courseware_block->progression === Courseware::PROGRESSION_SEQ  ? ' selected' : '' ?>>  <?= _cw("sequentiell") ?> </option>
                    </select>
                </td>
            </tr>

            <?= $this->render_partial('courseware/_settings_editing_permission') ?>
            
            <tr>
                <td>
                    <label for="courseware-vipstab-visible">
                        <?= _cw('Vips-Reiter für AutorInnen entfernen') ?><br>
                        <dfn id="courseware-vipstab-visible-description">
                            <?= _cw('Wenn Sie diesen Schalter aktivieren, wird der Vips-Reiter für normale Teilnehmende entfernt.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input id="courseware-vipstab-visible"
                           name="courseware[vipstab_visible]"
                           type="checkbox" <?= $courseware_block->getVipsTabVisible() ? "checked" : "" ?>>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="courseware-section-navigation">
                        <?= _cw('Dritte Navigationsebene anzeigen') ?><br>
                        <dfn id="courseware-section-navigation-description">
                            <?= _cw('Wählen Sie hier aus wie die dritte Navigationsebene dargestellt werden soll.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <select name="courseware[section_navigation]" id="courseware-section-navigation">
                        <option value="default"  <?= (!$courseware_block->getSectionsAsChapters() && $courseware_block->getShowSectionNav()) ? "selected" : "" ?> ><?= _cw("Über dem Seiteninhalt horizontal anzeigen") ?></option>
                        <option value="chapter" <?= $courseware_block->getSectionsAsChapters() ? "selected" : "" ?>><?= _cw("Links in der Kapitelnavigation anzeigen") ?></option>
                        <option value="hide" <?= $courseware_block->getShowSectionNav() ? "" : "selected" ?> ><?= _cw("Nicht anzeigen") ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th colspan="2"><?= _cw('Blockeinstellungen') ?></th>
            </tr>

            <?= $this->render_partial('courseware/_settings_discussionblock') ?>

            <tr>
                <th colspan="2"><?= _cw('Selbsttests') ?></th>
            </tr>

            <tr>
                <td>
                    <label for="max-tries">
                        <?= _cw('Anzahl Versuche') ?><br>
                        <dfn id="courseware-max-tries-description">
                            <?= _cw('Die Anzahl der Versuche, die ein Student beim Lösen von Aufgaben eines Selbsttests hat, bevor die Lösung der Aufgabe angezeigt wird.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <input id="max-tries" type="number" min="0" name="courseware[max-tries]" value="<?= htmlReady($courseware_block->max_tries) ?>" aria-describedby="courseware-max-tries-description">
                    <label style="margin-left: 20px;" for="num-counts-infinity">
                        <?= _cw('Unbegrenzt') ?>
                        <input id="max-tries-infinity" type="checkbox" name="courseware[max-tries-infinity]" aria-describedby="courseware-max-tries-description">
                    </label>
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
                </td>
            </tr>

        </tbody>

        <tfoot>
            <tr>
                <td class="table_row_odd" colspan="2" align="center">
                    <?= Button::create(_cw('Übernehmen'), 'submit', array('title' => _cw('Änderungen übernehmen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
