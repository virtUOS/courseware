<?
$body_id = 'mooc-courseware-index';
?>

<h1> Courseware: <?= htmlReady($courseware->title) ?></h1>

<ol class=chapters>
<?= $this->render_partial_collection('courseware/_chapter', $courseware->chapters) ?>
</ol>

<ol class=sections>
<?= $this->render_partial_collection('courseware/_section', $subchapter->sections) ?>
</ol>

<section class=content>
  <p>Diese Section hat <?= count($section->blocks) ?> Block/s.</p>

  <?= $this->render_partial_collection('courseware/_block', $section->blocks) ?>

</section>
