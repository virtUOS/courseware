<label>
    <?= _cw('Blubber-Diskussionsblock aktivieren') ?><br>
    <dfn id="courseware-discussionblock-description">
        <?= _cw('Der Blubber-Diskussionsblock bietet eine Möglichkeit zur Kommunikation zwischen den Teilnehmern. Durch die Aktivierung muss aber der Blubber-Reiter entfernt werden.'); ?>
    </dfn>
    <select id="courseware-discussionblock"
            name="courseware[discussionblock_activation]"
            class="size-s"
    >
        <? $blubber = $courseware_block->getDiscussionBlockActivation() ?>
        <option value="1" <? if ($blubber): ?>selected<? endif ?>><?= _cw('Ja')?></option>
        <option value="0" <? if (!$blubber): ?>selected<? endif ?>><?= _cw('Nein')?></option>
    </select>
</label>
