<?php
/** @var \Mooc $plugin */
/** @var string[] $fields */

/** @var string $termsOfServiceUrl */
$termsOfServiceUrl = PluginEngine::getLink($plugin, array(), 'registrations/terms')
?>
<form class="signup" method="post" action="<?= $controller->url_for('registrations/create') ?>">
    <?php
    foreach ($fields as $field) {
        if (substr($field, 0, 6) === 'field:') {
            $separatorPos = strpos($field, '|');
            $required = false;
            $label = null;

            if ($separatorPos !== false) {
                $label = substr($field, $separatorPos + 1);
                $fieldName = substr($field, 6, $separatorPos - 6);
            } else {
                $fieldName = substr($field, 6);
            }

            if (substr($fieldName, -1) === '*') {
                $fieldName = substr($fieldName, 0, -1);
                $required = true;
            }

            // map configured field names to user properties
            switch ($fieldName) {
                case 'firstname':
                    $fieldName = 'vorname';
                    break;
                case 'lastname':
                    $fieldName = 'nachname';
                    break;
                case 'email':
                    $fieldName = 'mail';
                    break;
                default:
                    // skip the field if it is not recognised
                    continue;
            }

            if ($fieldName === 'terms_of_service') {
                ?>
                <input type="checkbox" name="accept_tos" id="mooc_sign_up_terms_of_service"<?= $required ? ' required' : '' ?>
                <label for="mooc_sign_up_terms_of_service">
                    Ich erkläre mich mit den <a href="<?=$termsOfServiceUrl?>" target="_blank">Nutzungsbedingungen</a> einverstanden.
                </label>
                <?php
            } else {
                printf('<label for="mooc_sign_up_%s">%s</label>:<br>', $fieldName, $label);
                printf(
                    '<input type="text" name="%s" id="mooc_sign_up_%s" placeholder="%s"%s>',
                    $fieldName,
                    $fieldName,
                    $label,
                    $required ? ' required' : ''
                );
            }
        } else {
            echo formatReady($field);
        }
    }
    ?>

    <br>

    <input type="hidden" name="type" value="create">
    <input type="hidden" name="moocid" value="<?= htmlReady($cid) ?>">
    <?= Studip\Button::create(_('Jetzt anmelden')) ?>
</form>
