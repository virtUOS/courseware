<? $body_id = 'mooc-courseware-index'; ?>

<?= $courseware_block->render($view, $context) ?>

<?= $this->render_partial('courseware/_requirejs') ?>
