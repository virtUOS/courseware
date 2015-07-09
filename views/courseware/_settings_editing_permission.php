<tr>
    <td>
        <label for="courseware-editing-permission">
            <?= _('Editierberechtigung für Tutoren') ?><br>
            <dfn id="courseware-editing-permission-description">
                <?= _('Wenn Sie diesen Schalter aktivieren, dürfen neben den Dozenten auch Tutoren Ihre Courseware editieren.'); ?>
            </dfn>
        </label>
    </td>
    <td>
        <input id="courseware-editing-permission"
               name="courseware[editing_permission]"
               <? if ($is_tutor) : ?> disabled <? endif ?>
               type="checkbox" <?= $courseware_block->getEditingPermission() === \Mooc\UI\Courseware\Courseware::EDITING_PERMISSION_TUTOR ? "checked" : "" ?>>
    </td>
</tr>
