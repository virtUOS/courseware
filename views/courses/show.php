<?
$body_id = 'mooc-courses-show';
?>

<h1><?= htmlReady($course->name) ?></h1>
<p class=subtitle><?= htmlReady($course->untertitel) ?></p>

<article class=description>
  <h1>Kursbeschreibung</h1>
  <p><?= htmlReady($course->beschreibung) ?></p>
</article>

<article class=requirements>
  <h1>Voraussetzungen</h1>
  <p><?= htmlReady($course->vorrausetzungen) ?></p>
</article>

<div class=clear></div>

<? if ($container['current_user_id'] === "nobody") : ?>
  <?= \Studip\LinkButton::create("Zur Anmeldung", $controller->url_for('registrations/new', array('moocid' => $course->id))) ?>
<? endif ?>

<? $infobox = $this->render_partial('courses/_show_infobox') ?>

<?= $this->render_partial('courses/_requirejs') ?>
