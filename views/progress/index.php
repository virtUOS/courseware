<?php
$body_id = 'mooc-progress-index';

$progress = function ($block, $format = "") {
    return ceil($block['progress'] * 100) . $format;
};

// FIXME: DRY this; titleize is already defined in models/mooc/Container.php
$titleize = function ($content) {
    if (preg_match('/^\+\+/', $content)) {
        $content = "<span class=indented>" . substr($content, 2) . "</span>";
    }
    return $content;
};
?>

<div id="layout-content">


<section id="courseware" class="active-section<?= $mode == 'total' ? ' total-progress' :'' ?>">

  <? if ($mode=='total'): ?>
    <aside>
      <ol class="chapters">
        <? foreach ($members as $m): ?>
          <li class="chapter <?= ($current_user && $m->user_id == $current_user->user_id) ? "selected":"" ?>">
            <div class="title">
              <a class="navigate" href="<?= $controller->url_for('progress', array('uid' => $m->user_id)) ?>">
                <span class="user_avatar">
                    <?=Avatar::getAvatar($m->user_id)->getImageTag(Avatar::NORMAL);?>
                </span>
                <?= $m->getUserFullname(); ?>
              </a>
            </div>
            <? if ($current_user && $m->user_id == $current_user->user_id) : ?>
                <ol class="subchapters">
                  <li class="subchapter">
                    <div class="title">
                      <a class="navigate" href="#progress">Fortschritt</a>
                    </div>
                  </li>
                  <li class="subchapter">
                    <div class="title">
                      <a class="navigate" href="#comm">Kommunikation</a>
                    </div>
                  </li>
                    <li class="subchapter">
                        <div class="title">
                            <a href="<?=URLHelper::getLink('dispatch.php/profile?username=' . $m->username)?>" class="navigate">Profil</a>
                        </div>
                    </li>
                </ol>
            <? endif ?>
          </li>
        <? endforeach ?>
      </ol>
    </aside>
  <? endif ?>
<a name="progress"></a>
  <div class=" <?=$mode=='total' ? 'active-section':''?>">

    <h1>Fortschrittsübersicht <? if ($mode=='total' && $current_user) echo "für ".$current_user->getFullname(); ?></h1>

    <table class=chapters>
      <? foreach ($courseware['children'] as $chapter) : ?>
        <tr class=chapter>
            
                <th colspan=2>
                    <a href="<?= $controller->url_for('courseware', array('selected' => $chapter['id'])) ?>">
                    <?= $titleize(htmlReady($chapter['title'])) ?>
                    <? if (sizeof($chapter['children'])) : ?>
                        <!---<span class=progress><?//= $progress($chapter, "%") ?></span> -->
                    <? endif ?>
                    </a>
                </th>
           
        </tr>

        <? foreach ($chapter['children'] as $subchapter) : ?>
          <tr class=subchapter>
            
                <th>
                    <a href="<?= $controller->url_for('courseware', array('selected' => $subchapter['id'])) ?>">
                    <?= $titleize(htmlReady($subchapter['title'])) ?>
                    <? if (sizeof($subchapter['children'])) : ?>
                        <!-- <span class=progress><?//= $progress($subchapter, "%") ?></span> -->
                    <? endif ?>
                    </a>
                </th>
            
            <td>
              <ol class=sections>
                <? foreach ($subchapter['children'] as $section) : ?>
                    <li>
                        <a href="<?= $controller->url_for('courseware', array('selected' => $section['id'])) ?>"
                        title="<?= htmlReady($section['title']) ?>"
                        data-progress="<?= $progress($section) ?>">
                            <progress value=<?= $progress($section) ?> max=100><span><?= $progress($section) ?></span>%</progress>
                        </a>
                    </li>
                    
                <? endforeach ?>
              </ol>
            </td>
          </tr>
        <? endforeach ?>

      <? endforeach ?>
    </table>


    <?// $thread = $discussion->thread; ?>
<!--
    <div id="comm" class="block-content">

    <article class="thread loading" id="<?= htmlReady($thread->id) ?>" data-courseid="<?= htmlReady($discussion->cid) ?>">
        <header>
            <h1>
                Persönliche Kommunikation mit
                <? if ($mode=='total' && $current_user) : ?>
                    <?= $current_user->getFullname() ?>
                <? else: ?>
                    <?= _("dem Referenten") ?>
                <? endif ?>
            </h1>
        </header>
        <ul class="comments"></ul>

        <div class="comment-writer">
            <textarea placeholder="<?= _("Kommentiere dies") ?>"
                      aria-label="<?= _("Kommentiere dies") ?>"></textarea>
        </div>

    </article>

    </div>
-->
</section>

</div>

<?= $this->render_partial('courseware/_requirejs', array('main' => 'main-progress')) ?>
