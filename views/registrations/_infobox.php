<div class="mooc-registrations">
    <p>
        Sie haben schon einen OHN-Account?
    </p>
    <form class="login">
        <input type="text" name="username" placeholder="<?= _('Benutzername') ?>" required><br>
        <input type="password" name="password" placeholder="<?= _('Passwort') ?>" required><br>
        <br>
        <label style="position: relative;">
            <span style="display: block; padding: 0 10px 0 30px;"><?= _('Ich bin mit den Nutzungs- bedingungen einverstanden.') ?></span>
            <input type="checkbox" name="accept_tos" value="yes" required style="position: absolute; top: 1.1em; left: 3px;"> 
        </label>
        <?= Studip\Button::createAccept(_('Jetzt anmelden')) ?>
    </form>
</div>