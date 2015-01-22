<?php
/** @var \Mooc $plugin */

/** @var \Seminar_Perm $perm */
$perm = $GLOBALS['perm'];

$body_id = 'mooc-courses-show';

if (class_exists('Sidebar')):
    $sidebar = Sidebar::Get();
    $actions = new ActionsWidget();
    $actions->setTitle('Vorschau');
    $actions->insertElement(new WidgetElement($this->render_partial('courses/_show_sidebar')), 'navigation');
    $sidebar->addWidget($actions);
else:
    $infobox = $this->render_partial('courses/_show_infobox');
endif;
?>

<h1><?= htmlReady($course->name) ?></h1>
<p class=subtitle><?= htmlReady($course->untertitel) ?></p>

<article class=description>
  <h1>Kursbeschreibung</h1>
  <p><?= formatReady($course->beschreibung) ?></p>
</article>

<article class=requirements>
  <h1>Voraussetzungen</h1>
  <p><?= formatReady($course->vorrausetzungen) ?></p>
</article>

<div class=clear></div>

<? if (!$perm->have_studip_perm('autor', $course->id)): ?>
  <?= \Studip\LinkButton::create("Zur Anmeldung", $controller->url_for('registrations/new', array('moocid' => $course->id))) ?>
<? endif ?>

<?= $this->render_partial('courses/_requirejs') ?>
