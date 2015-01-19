<?php
/** @var \Mooc $plugin */

$body_id = 'mooc-courses-show';

$sidebar = Sidebar::Get();
$actions = new ActionsWidget();
$actions->setTitle('Vorschau');
$actions->insertElement(new WidgetElement($this->render_partial('courses/_show_infobox')), 'navigation');
$sidebar->addWidget($actions);
?>

<h1><?= htmlReady($course->name) ?></h1>
<p class=subtitle><?= htmlReady($course->untertitel) ?></p>

<article class=description>
  <h1>Kursbeschreibung</h1>
  <p><?= nl2br(htmlReady($course->beschreibung)) ?></p>
</article>

<article class=requirements>
  <h1>Voraussetzungen</h1>
  <p><?= nl2br(htmlReady($course->vorrausetzungen)) ?></p>
</article>

<div class=clear></div>

<? if ($plugin->getCurrentUserId() === "nobody") : ?>
  <?= \Studip\LinkButton::create("Zur Anmeldung", $controller->url_for('registrations/new', array('moocid' => $course->id))) ?>
<? endif ?>

<?= $this->render_partial('courses/_requirejs') ?>
