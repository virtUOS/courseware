<?php
$body_id = 'mooc-progress-index';

$progress = function ($block, $format = "") {
    return ceil($block['progress'] * 100) . $format;
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
                </ol>
            <? endif ?>
          </li>
        <? endforeach ?>
      </ol>
    </aside>
  <? endif ?>
<a name="progress"></a>
  <div class=" <?=$mode=='total' ? 'active-section':''?>">

    <h1>Fortschrittsübersicht <? if ($mode=='total' && $current_user) echo "für ".$current_user->getUserFullname(); ?></h1>

    <table class=chapters>
      <? foreach ($courseware['children'] as $chapter) : ?>
        <tr class=chapter>
          <th colspan=2>
            <?= htmlReady($chapter['title']) ?>
            <? if (sizeof($chapter['children'])) : ?>
              <span class=progress><?= $progress($chapter, "%") ?></span>
            <? endif ?>
          </th>
        </tr>

        <? foreach ($chapter['children'] as $subchapter) : ?>
          <tr class=subchapter>
            <th>
              <?= htmlReady($subchapter['title']) ?>
              <? if (sizeof($subchapter['children'])) : ?>
                <span class=progress><?= $progress($subchapter, "%") ?></span>
              <? endif ?>
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

      <p>&nbsp;</p>
      <a name="comm"></a>
<h1>
    Persönliche Kommunikation mit <? if ($mode=='total' && $current_user) echo $current_user->getUserFullname(); else echo "dem Referenten"; ?>
    <!-- TODO: Referent ersetzen -->
</h1>

<? foreach ($blocks as $block ) : ?>

    <?
    $ui_block = $container['block_factory']->makeBlock($block);
    $html = $ui_block->render('student', array());
    ?>

    <section class="contentbox">

        <section id=block-<?= $block->id ?>
                 class="block <?= $block->type ?>"
                 data-blockid="<?= $block->id ?>"
                 data-blocktype="<?= $block->type ?>">

            <div class="block-content" data-view="student">
                <?= $html ?>
            </div>
        </section>


    </section>
<? endforeach ?>

</section>

</div>

<?= $this->render_partial('courseware/_requirejs', array('main' => 'main-discussions')) ?>
