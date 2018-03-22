<tr>
    <td>
        <label for="courseware-editing-permission">
            <?= _cw('Editierberechtigung für TutorInnen') ?><br>
            <dfn id="courseware-editing-permission-description">
                <?= _cw('Wenn Sie diesen Schalter aktivieren, dürfen neben den DozentInnen auch TutorInnen Ihre Courseware editieren.'); ?>
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
