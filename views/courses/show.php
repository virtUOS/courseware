<?php
/** @var \Mooc $plugin */

$body_id = 'mooc-courses-show';

if (class_exists('Sidebar')):
    NotificationCenter::addObserver('MoocSidebarOverview', 'addPreview', 'SidebarWillRender');

    // #TODO: find a better solution for this ugly piece of code
    $GLOBALS['mooc_widget'] = new WidgetElement($this->render_partial('courses/_show_sidebar'));

    class MoocSidebarOverview {
        function addPreview($event, $sidebar) {
            $actions = new ActionsWidget();
            $actions->setTitle(null);
            $actions->insertElement($GLOBALS['mooc_widget'], 'navigation');
            $sidebar->insertWidget($actions, ':first');
        }
    }

else:
    $infobox = $this->render_partial('courses/_show_infobox');
endif;
?>

<? if ($preliminary) : ?>
    <?= MessageBox::info(_('Sie sind bereits für diesen Kurs eingetragen, allerdings können Sie auf die Kursinhalte erst zugreifen, sobald der Kurs begonnen hat!')) ?>
<? endif ?>

<h1><?= htmlReady($course->name) ?></h1>
<p class=subtitle><?= htmlReady($course->untertitel) ?></p>

<article class=requirements>
  <h1>Voraussetzungen</h1>
  <p><?= formatReady($course->vorrausetzungen) ?></p>
</article>

<article class=description>
  <h1>Kursbeschreibung</h1>
  <p><?= formatReady($course->beschreibung) ?></p>
</article>



<div class=clear></div>


<?= $this->render_partial('courses/_requirejs') ?>
