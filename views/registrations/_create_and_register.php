<?php
/** @var \Mooc $plugin */
/** @var array $fields */
/** @var array $userInput */

/** @var string $termsOfServiceUrl */
$termsOfServiceUrl = PluginEngine::getLink($plugin, array(), 'registrations/terms');

/** @var string $privacyPolicyUrl */
$privacyPolicyUrl = PluginEngine::getLink($plugin, array(), 'registrations/privacy_policy');
?>
<form class="signup" method="post" action="<?= $controller->url_for('registrations/create') ?>">
    <?php foreach ($fields as $field): ?>
        <?php if (is_array($field) && $field['fieldName'] === 'accept_tos'): ?>
            <input type="checkbox" name="accept_tos" id="mooc_sign_up_terms_of_service"<?= $field['required'] ? ' required' : '' ?>>
            <label for="mooc_sign_up_terms_of_service" class="tos">
                Ich akzeptiere die <a href="<?= $termsOfServiceUrl ?>" target="_blank">Nutzungsbedingungen</a>
                und die <a href="<?= $privacyPolicyUrl ?>" target="_blank">Datenschutzerklärung</a>.
            </label>
        <?php elseif (is_array($field)): ?>
            <label for="mooc_sign_up_<?= $field['fieldName'] ?>"<?= $field['required'] ? ' class="required"' : '' ?>>
                <?= $field['label'] ?>
                <?php if ($field['required']): ?>
                    *
                <?php endif ?>
            </label>
            <?php if (is_array($field['choices'])): ?>
                <select name="<?= $field['fieldName'] ?>" id="mooc_sign_up_<?= $field['fieldName'] ?>"<?= $field['required'] ? ' required' : '' ?>>
                    <option><?=_('--')?></option>
                    <?php foreach ($field['choices'] as $choice): ?>
                        <option value="<?=htmlReady($choice)?>"<?=$userInput[$field['fieldName']] == $choice ? ' selected' : ''?>><?=htmlReady($choice)?></option>
                    <?php endforeach ?>
                </select>
            <?php else: ?>
            <input type="text"
                name="<?= $field['fieldName'] ?>"
                id="mooc_sign_up_<?= $field['fieldName'] ?>"
                placeholder="<?= $field['label'] ?>"
                value="<?= htmlReady($userInput[$field['fieldName']]) ?>"<?= $field['required'] ? ' required' : '' ?>>
            <?php endif ?>
        <?php else: ?>
            <span class="mooc_registration_form_text"><?= $field ?></span>
        <?php endif ?>
    <?php endforeach ?>

    <br>

    <input type="hidden" name="type" value="create">
    <input type="hidden" name="moocid" value="<?= htmlReady($cid) ?>">
    <?= Studip\Button::create(_('Jetzt anmelden')) ?>
</form>
