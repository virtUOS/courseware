<?php
$body_id = 'mooc-progress-index';

$progress = function ($block, $format = "") {
    return ceil($block['progress'] * 100) . $format;
};
?>

<h1><?= $courseware['title'] ?></h1>
<br>
<ul id="overview-chapter-nav">
    <li class="overview-chapter-nav-arrow" id="overview-chapter-nav-left"></li>
    <li id="chapter-container" style="width:calc(100% - 140px);">
            <ul style="width: <?= count($courseware['children'])*200;?>px;" id="chapter-list">
                <? foreach ($courseware['children'] as $chapter) : ?>
                    <li class="course-box" data-course="chapter-<?= $chapter['id'] ?>">
                        <p><?= htmlReady($chapter['title']) ?></p>
                        <? if (sizeof($chapter['children'])) : ?>
                            <div style="margin:0 auto;" class="progress-circle p<?= $progress($chapter) ?>">
                               <span><?= $progress($chapter, "%") ?></span>
                               <div class="left-half-clipper">
                                  <div class="first50-bar"></div>
                                  <div class="value-bar"></div>
                               </div>
                            </div>
                        <? endif ?>
                    </li>
                <? endforeach ?>
            </ul>
            <div class="clear"></div>
    </li>
    <li class="overview-chapter-nav-arrow" id="overview-chapter-nav-right"></li>
</ul>

<? foreach ($courseware['children'] as $chapter) : ?>
    <div id="chapter-<?= $chapter['id'] ?>" class="overview-chapter-details">
        <h1><?= htmlReady($chapter['title']) ?></h1>
        <table class="chapters">
            <? foreach ($chapter['children'] as $subchapter) : ?>
              <tr class=subchapter>
                <th class="subchapter-description">
                    <a href="<?= $controller->url_for('courseware', array('selected' => $subchapter['id'])) ?>">
                    <?= htmlReady($subchapter['title']) ?>
                    </a>
                    <? if (sizeof($subchapter['children'])) : ?>
                        <p data-progress="<?= $progress($subchapter) ?>">
                            <progress value=<?= $progress($subchapter) ?> max=100></progress>
                        </p>
                    <? endif ?>
                </th>
                <td>
                  <ol class=sections>
                    <? foreach ($subchapter['children'] as $section) : ?>
                      <li>
                        <a href="<?= $controller->url_for('courseware', array('selected' => $section['id'])) ?>"
                           title="<?= htmlReady($section['title']) ?>"
                           data-progress="<?= $progress($section) ?>">
                          <progress value=<?= $progress($section) ?> max=100></progress>
                        </a>
                      </li>
                    <? endforeach ?>
                  </ol>
                </td>
                <td></td>
              </tr>
            <? endforeach ?>
        </table>
    </div>
<? endforeach ?>

<script>
    $(".overview-chapter-details").first().show();
    var $scroll = 0, $container = $('#chapter-container'), $list = $('#chapter-list')
    var $maxscroll = $list.outerWidth() - $container.outerWidth()
    $( "#overview-chapter-nav-left" ).click(function() {
        if($scroll > 0) {
            $scroll -= 200;
            $container.animate({scrollLeft: $scroll}, 400);
        }
    });
    $( "#overview-chapter-nav-right" ).click(function() {
        if ($scroll < $maxscroll) {
            $scroll += 200;
            $container.animate({scrollLeft: $scroll}, 400);
        }
    });
    $( ".course-box" ).click(function() {
        $(".overview-chapter-details").hide();
        var $course = $(this).data("course");
        $('#'+$course).show();
    });
    
</script>
