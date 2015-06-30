<?
use Studip\Button;
use Mooc\UI\Courseware\Courseware;
?>

<form method="post" action="<?= $controller->url_for('courseware/settings') ?>">
    <?= CSRFProtection::tokenTag() ?>

    <table id="main_content" class="default">
        <colgroup>
            <col width="50%">
            <col width="50%">
        </colgroup>

        <caption>
            <?= _('Courseware-Einstellungen') ?>
        </caption>

        <tbody>
            <!--
            <tr>
                <th colspan="2"><?= _('Allgemeines') ?></th>
            </tr>
            -->

            <tr>
                <td>
                    <label for="courseware-title">
                        <?= _('Titel der Courseware') ?><br>
                        <dfn id="courseware-title-description">
                            <?= _('Der Titel der Courseware erscheint als Beschriftung des Courseware-Reiters. Sie können den Reiter also z.B. auch "Online-Skript", "Lernmodul" o.ä. nennen.'); ?>
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
                        <?= _('Art der Kapitelabfolge') ?><br>
                        <dfn id="courseware-progression-description">
                            <?= _('Bei freier Kapitelabfolge können alle sichtbaren Kapitel in beliebiger Reihenfolge ausgewählt werden. Bei sequentieller Abfolge müssen alle vorangehenden Unterkapitel erfolgreich abgeschlossen sein, damit ein Unterkapitel ausgewählt und angezeigt werden kann.'); ?>
                        </dfn>
                    </label>
                </td>
                <td>
                    <? $progression_type = $courseware_block->progression; ?>
                    <select name="courseware[progression]" id="courseware-progression" aria-describedby="courseware-progression-description">
                        <option value="free"<?= $courseware_block->progression === Courseware::PROGRESSION_FREE ? ' selected' : '' ?>> <?= _("frei") ?> </option>
                        <option value="seq"<?= $courseware_block->progression === Courseware::PROGRESSION_SEQ  ? ' selected' : '' ?>>  <?= _("sequentiell") ?> </option>
                    </select>
                </td>
            </tr>
        </tbody>

        <tfoot>
            <tr>
                <td class="table_row_odd" colspan="2" align="center">
                    <?= Button::create(_('Übernehmen'), 'submit', array('title' => _('Änderungen übernehmen'))) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
