<p>Sie haben schon einen OHN-Account?</p>
<? if ($GLOBALS['user']->id === 'nobody') : ?>
  <?= $this->render_partial('registrations/_login_and_register') ?>
<? else :?>
  <?= $this->render_partial('registrations/_register') ?>
<? endif ?>
