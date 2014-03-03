<?
$course = $_index_item;
?>
<article>
	<div class="course-avatar-wrapper">
  	<?= CourseAvatar::getAvatar($course->id)->getImageTag(CourseAvatar::MEDIUM) ?>
	</div>
  	

  <h1><?= htmlReady($course->name) ?></h1>
  <p class=subtitle><?= htmlReady($course->untertitel) ?></p>

  <? if ($GLOBALS['perm']->have_studip_perm("autor", $course->id)) : ?>
    <?= \Studip\LinkButton::create("Zum Kurs", $controller->url_for('courses/show/' . $course->id, array('cid' => $course->id))) ?>
  <? else : ?>
    <?= \Studip\LinkButton::create("Mehr…", $controller->url_for('courses/show/' . $course->id, array('moocid' => $course->id))) ?>
  <? endif ?>
</article>
