<?
$course = $_index_item;
?>
<article>
  <?= CourseAvatar::getAvatar($course->id)->getImageTag(CourseAvatar::MEDIUM) ?>

  <h1><?= htmlReady($course->name) ?></h1>
  <p class=subtitle><?= htmlReady($course->untertitel) ?></p>

  <?= \Studip\LinkButton::create("Mehr…", $controller->url_for('courses/show', array('cid' => $course->id))) ?>
</article>
