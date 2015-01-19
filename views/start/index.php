<?php
/** @var \Course[] $courses */
?>

<section id="mooc-course-list">
    <?= $this->render_partial_collection('courses/_index_item', $courses) ?>
</section>
