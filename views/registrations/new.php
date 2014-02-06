<?
$body_id = 'mooc-registrations-index';
?>

<? if (isset($this->flash['error'])) : ?>
<?= MessageBox::error(htmlReady($this->flash['error']))?>
<? endif ?>

<h1>
  <? printf(_('Anmeldung für "%s"'), htmlReady($course->name)) ?>
</h1>

<? if ($container['current_user_id'] === 'nobody') : ?>
  <?= $this->render_partial('registrations/_create_and_register') ?>
  <? $infobox = $this->render_partial('registrations/_infobox') ?>
<? else : ?>
  <?= $this->render_partial('registrations/_tos') ?>
  <?= $this->render_partial('registrations/_register') ?>
<? endif ?>
