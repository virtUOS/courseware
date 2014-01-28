<?
$body_id = 'mooc-courses-index';
?>

<h1>Alle Kurse</h1>

<section id=course-list>
  <?= $this->render_partial_collection('courses/_index_item', $courses) ?>
</section>
