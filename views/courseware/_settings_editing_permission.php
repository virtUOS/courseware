<label>
    <?= _cw('Editierberechtigung für TutorInnen') ?><br>
    <dfn id="courseware-editing-permission-description">
        <?= _cw('Wenn Sie diesen Schalter aktivieren, dürfen neben den DozentInnen auch TutorInnen Ihre Courseware editieren.'); ?>
    </dfn>
    <select id="courseware-editing-permission"
        name="courseware[editing_permission]"
        class="size-s"
        <? if ($is_tutor) : ?> disabled <? endif ?>
    >
        <? $tutor_can_edit = $courseware_block->getEditingPermission() === \Mooc\UI\Courseware\Courseware::EDITING_PERMISSION_TUTOR ?>
            <option value="1" <? if ($tutor_can_edit): ?>selected<? endif ?>><?= _cw('Ja')?></option>
            <option value="0" <? if (!$tutor_can_edit): ?>selected<? endif ?>><?= _cw('Nein')?></option>
    </select>
</label>


