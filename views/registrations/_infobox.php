<p>Sie haben schon einen OHN-Account?</p>
<form class="login">
  <input type="text" name="username" placeholder="<?= _('Benutzername') ?>" required><br>
  <input type="password" name="password" placeholder="<?= _('Passwort') ?>" required><br>
  <br>
  <label class=tos>
    <input type="checkbox" name="accept_tos" value="yes" required>
    <span><?= _('Ich bin mit den Nutzungsbedingungen einverstanden.') ?></span>
  </label>
  <?= Studip\Button::createAccept(_('Jetzt anmelden')) ?>
</form>
