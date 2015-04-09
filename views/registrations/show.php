<?
$body_id = 'mooc-registrations-index';
?>

<h1><?= htmlReady($course->name) ?></h1>

<h2><?= _('Vielen Dank für Ihre Anmeldung!') ?></h2>

<div id="messages"></div>

<b><?= _('Bitte loggen Sie sich ein!') ?></b>
<br>

<?= _('Wir haben soeben eine E-Mail an die angegebene Adresse geschickt, in der Ihr Passwort enthalten ist.'
        . ' Mit diesem Passwort können Sie sich nun einloggen.') ?>
<br><br>
<?= _('Falls Sie die E-Mail nicht erhalten haben, können Sie sie sich erneut schicken lassen.') ?><br>

<?= Studip\LinkButton::create(_('E-Mail erneut senden'), 'javascript:', array(
    'name'         => 'resend_mail',
    'data-user-id' => $user->getId(),
    'data-mooc-id' => $course->getId()
)) ?>
<br>
<br>

<form class="signin" method="post" action="<?= $controller->url_for('courses/show/'. $course->getId() .'?cid=') ?>">
    <input type="text" name="username" placeholder="<?= _('Benutzername') ?>" value="<?= $user->username ?>" required><br>
    <input type="password" name="password" placeholder="<?= _('Passwort') ?>" required><br>
    <?= Studip\Button::create(_('Jetzt einloggen')) ?>
</form>

<?= $this->render_partial('registrations/_js_templates') ?>