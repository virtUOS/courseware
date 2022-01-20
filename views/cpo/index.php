<?php
$body_id = 'courseware-cpo-index';

$progress = function ($block, $format = "") {
    return ceil($block['progress'] * 100) . $format;
};

$monate = array(1=>"Jan", 2=>"Feb", 3=>"Mär", 4=>"Apr", 5=>"Mai", 6=>"Jun", 7=>"Jul", 8=>"Aug", 9=>"Sep", 10=>"Okt",11=>"Nov", 12=>"Dez");

?>
<h1 style="float: left">
    <?= _cw('Fortschrittsübersicht für Lehrende') ?>
    <? if ($uid) : ?>
    - <?= get_fullname($uid); ?>
    <? endif ?>
</h1>
<div class="clear"></div>
<ul id="overview-chapter-nav">
    <li class="overview-chapter-nav-arrow" id="overview-chapter-nav-left"></li>
    <li id="chapter-container" style="width:calc(100% - 140px);">
            <ul style="width: <?= count($courseware['children'])*203;?>px;" id="chapter-list">
                <? foreach ($courseware['children'] as $chapter) : ?>
                    <li class="course-box" data-course="chapter-<?= $chapter['id'] ?>">
                        <p><?= htmlReady($chapter['title']) ?></p>
                            <div style="margin:0 auto;" class="progress-circle p<?= $progress($chapter) ?> <? if($progress($chapter) > 50):?>over50 <? endif?>">
                               <span><?= $progress($chapter, "%") ?></span>
                               <div class="left-half-clipper">
                                  <div class="first50-bar"></div>
                                  <div class="value-bar"></div>
                               </div>
                            </div>
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
            <? if ($chapter['children']): ?>
                <? foreach ($chapter['children'] as $subchapter) : ?>
                <tr class=subchapter>
                    <th class="subchapter-description">
                        <a href="<?= $controller->url_for('courseware', array('selected' => $subchapter['id'])) ?>">
                        <?= htmlReady($subchapter['title']) ?>
                        </a>
                        <p data-progress="<?= $progress($subchapter) ?>">
                            <progress value=<?= $progress($subchapter) ?> max=100></progress>
                        </p>
                    </th>
                    <td>
                    <ol class=sections>
                        <? if ($subchapter['children']): ?>
                            <? foreach ($subchapter['children'] as $section) : ?>
                            <li>
                                <a href="<?= $controller->url_for('courseware', array('selected' => $section['id'])) ?>"
                                data-progress="<?= $progress($section) ?>"><span class="progress-section-title">
                                        <?  $title = htmlReady($section['title']);
                                            if(strlen($title) > 16) { $title =  substr($title, 0, 12) . "...";}
                                            echo $title;
                                        ?>
                                    </span>
                                <progress value=<?= $progress($section) ?> max=100></progress>
                                </a>
                            </li>
                            <? endforeach ?>
                        <? else: ?>
                            <?= _cw('Dieses Unterkatpitel ist leer.')?>
                        <? endif ?>
                    </ol>
                    </td>
                    <td>
                        <? if($subchapter['date'] != ''):?>
                        <div class="overview-date" title="<?= htmlReady($subchapter['title'])._cw(' wurde von einem Teilnehmer zuletzt am ') . date('d.m.Y', strtotime($subchapter['date']))._cw(' bearbeitet')?>">
                            <p class="overview-date-month"><?= $monate[date('n', strtotime($subchapter['date']))]?></p>
                            <p class="overview-date-day"><?= date('d', strtotime($subchapter['date']))?></p>
                            <p class="overview-date-time"><?= date('H:i', strtotime($subchapter['date']))?></p>
                        </div>
                        <? endif?>
                    </td>
                </tr>
                <? endforeach ?>
            <? else: ?>
                <?= _cw('Dieses Kapitel ist leer.') ?>
            <? endif ?>
        </table>
    </div>
<? endforeach ?>

<script>
    $(".course-box").first().addClass("active-box");
    $(".overview-chapter-details").first().show();
    var $scroll = 0, $container = $('#chapter-container'), $list = $('#chapter-list')
    var $maxscroll = $list.outerWidth() - $container.outerWidth()
    $( "#overview-chapter-nav-left" ).click(function() {
        if($scroll > 0) {
            $scroll -= 200;
            $container.animate({scrollLeft: $scroll}, 200);
        }
    });
    $( "#overview-chapter-nav-right" ).click(function() {
        if ($scroll < $maxscroll) {
            $scroll += 200;
            $container.animate({scrollLeft: $scroll}, 200);
        }
    });
    $( "#overview-chapter-nav-left" ).on('mousedown', function(){
        (function scrollLeft() {
            if ($scroll > 0) {
                $scroll -= 20;
                $container.animate({scrollLeft: $scroll}, 20,'linear', function() {
                    window.timeout = setTimeout(scrollLeft(), 0);
                });
            }
        })();
    }).on('mouseup', function(){
        $container.stop();
        clearTimeout(window.timeout);
    });
    $( "#overview-chapter-nav-right" ).on('mousedown', function(){
        (function scrollRight() {
            if ($scroll < $maxscroll) {
                $scroll += 20;
                $container.animate({scrollLeft: $scroll}, 20,'linear', function() {
                    window.timeout = setTimeout(scrollRight(), 0);
                });
            }
        })();
    }).on('mouseup', function(){
        $container.stop();
        clearTimeout(window.timeout);
    });
    $( ".course-box" ).click(function() {
        $(".course-box" ).removeClass("active-box");
        $(this).addClass("active-box");
        $(".overview-chapter-details").hide();
        var $course = $(this).data("course");
        $('#'+$course).show();
    });
</script>
