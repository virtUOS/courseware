<? if (sizeof($courses)) : ?>
  <section id="mooc-course-list">
  <? foreach ($courses as $course) : ?>
      <article>
          <div class="course-avatar-wrapper">
              <img class="course-avatar-medium course-<?= $course->id ?>"
                   alt="<?= $name = htmlReady($course->name) ?>"
                   title="<?= $name ?>"
                   src="<?= $preview_images[$course->id] ?: CourseAvatar::getAvatar($course->id)->getURL(CourseAvatar::MEDIUM) ?>" />
          </div>


          <h1><?= $name ?></h1>
          <p class=subtitle><?= htmlReady($course->untertitel) ?></p>

          <?= \Studip\LinkButton::create(_('Kurs anzeigen'),
                                         PluginEngine::getLink($plugin,
                                                               array('cid' => $course->id),
                                                               'courses/show/'.$course->id,
                                                               true)) ?>

          <? $suppose_to_kill_link = \URLHelper::getLink(
              "dispatch.php/my_courses/decline/$course->id",
              array(),
              true); ?>

          <a class="kill" href="<?= $suppose_to_kill_link  ?>"><?= _("Mitgliedschaft beenden") ?></a>
      </article>

  <? endforeach ?>
  </section>
<? else : ?>
  <p><?= _("Sie sind noch in keinem Mooc-Kurs eingetragen. ") ?></p>
  <?= \Studip\LinkButton::create('Zur Kursliste', PluginEngine::getURL($plugin, array(), 'courses/index')) ?>
<? endif ?>
