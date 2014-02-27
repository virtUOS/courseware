<? $body_id = 'mooc-courses-overview' ?>
<? if ($root && $view == 'student') : ?>
<?= Studip\LinkButton::create(_('Bearbeiten'), $controller->url_for('courses/overview/edit')); ?>
<? endif ?>

<? if ($view == 'author') : ?>
<form method="post" action="<?= $controller->url_for('courses/store_overview') ?>">
<? endif ?>
    
<?= $ui_block->render($view, $context) ?>

<? if ($view == 'author') : ?>
</form>
<? endif ?>
