<form class="signup" method="post" action="<?= $controller->url_for('registrations/create') ?>">
  <input type="text" name="vorname" placeholder="<?= _('Vorname') ?>" required><br>
  <input type="text" name="nachname" placeholder="<?= _('Nachname') ?>" required><br>
  <input type="email" name="mail" placeholder="<?= _('E-Mail-Adresse') ?>" required><br>
  <br>

  <?= $this->render_partial('registrations/_tos') ?>

  <label>
    <input type="checkbox" name="accept_tos" value="yes" required>
    <?= _('Einverstanden') ?>
  </label>

  <br>

  <input type="hidden" name="type" value="create">
  <input type="hidden" name="moocid" value="<?= htmlReady($cid) ?>">
  <?= Studip\Button::create(_('Jetzt anmelden')) ?>
</form>
