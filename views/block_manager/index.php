<? $body_id = 'courseware-blockmanager'; ?>
<div class="cw-blockmanager-wrapper cw-blockmanager-info" id="cw-blockmanager-info">
<?
if (count($errors) > 0) {
    if (count($errors) == 1) {
        echo'<div class="cw-blockmanager-title"><p>'._cw('Es ist ein Fehler aufgetreten').'</p></div>';
    } else {
        echo'<div class="cw-blockmanager-title"><p>'._cw('Es sind Fehler aufgetreten').'</p></div>';
    }
    echo '<ul class="cw-blockmanager-info-content">';
    foreach ($errors as $error):
        echo '<li>'.htmlReady($error).'</li>';
    endforeach;
    echo '</ul>';
}

if (count($warnings) > 0) {
    echo'<div class="cw-blockmanager-title"><p>'._cw('Warnung').'</p></div>';
    echo '<ul class="cw-blockmanager-info-content">';
    foreach ($warnings as $warning):
        echo '<li>'.htmlReady($warning).'</li>';
    endforeach;
    echo '</ul>';
}

if (count($successes) > 0) {
    echo'<div class="cw-blockmanager-title"><p>'._cw('Erfolg').'</p></div>';
    echo '<ul class="cw-blockmanager-info-content">';
    foreach ($successes as $success):
        echo '<li>'.htmlReady($success).'</li>';
    endforeach;
    echo '</ul>';
}
?>
</div>
<div class="cw-blockmanager-wrapper">
    <div class="cw-blockmanager-title">
        <p><?= _cw('Struktur bearbeiten') ?></p>
    </div>

    <ul class="chapter-list">
        <? foreach((array)$courseware['children'] as $chapter): ?>
            <li class="chapter-item" data-id="<?= $chapter['id']?>">
                <p class="chapter-description"><?= $chapter['title']?> 
                    <span>
                        <?= _cw('Kapitel') ?>
                        <? if($chapter['publication_date'] != null):?>
                            | <?= _cw('veröffentlichen') ?>: <?=$chapter['publication_date']?>
                        <? endif ?>
                        <? if($chapter['withdraw_date'] != null):?>
                            | <?= _cw('widerrufen') ?>: <?=$chapter['withdraw_date']?>
                        <? endif ?>
                        <? if(!$chapter['isPublished']):?><span class="structure-not-visible"></span><? endif?>
                    </span>
                </p>
                <ul class="subchapter-list">
                    <? foreach((array)$chapter['children'] as $subchapter): ?>
                        <li class="subchapter-item" data-id="<?= $subchapter['id']?>">
                            <p class="subchapter-description"><?= $subchapter['title'] ?>
                                <span>
                                    <?= _cw('Unterkapitel') ?>
                                    <? if($subchapter['publication_date'] != null):?>
                                        | <?= _cw('veröffentlichen') ?>: <?=$subchapter['publication_date']?>
                                    <? endif ?>
                                    <? if($subchapter['withdraw_date'] != null):?>
                                        | <?= _cw('widerrufen') ?>: <?=$subchapter['withdraw_date']?>
                                    <? endif ?>
                                    <? if(!$subchapter['isPublished']):?><span class="structure-not-visible"></span><? endif?>
                                </span>
                            </p>
                            <ul class="section-list">
                                <? foreach((array)$subchapter['children'] as $section):?>
                                    <li class="section-item" data-id="<?= $section['id']?>">
                                        <p class="section-description" <? if(strlen($section['title'])>20): ?>title="<?= $section['title'] ?>"<?endif?>><?= substr($section['title'], 0, 20)?><? if(strlen($section['title'])>20): ?>…<?endif?> <span><?= _cw('Abschnitt') ?></span></p>
                                        <ul class="block-list">
                                        <? if($section['children'] != null):?>
                                            <? foreach((array)$section['children'] as $block):?>
                                                <? $ui_block = $block['ui_block']?>
                                                <li class="block-item" data-id="<?= $block['id']?>">
                                                    <p class="block-description"><span class="block-icon cw-block-icon-<?=$block['type']?>"></span><?= $ui_block::NAME ?><? if(!$block['visible']):?><span class="block-not-visible"></span><? endif?>
                                                    </p>
                                                    <ul class="block-preview">
                                                        <? if(method_exists($ui_block, 'preview_view')): ?>
                                                            <li class="block-content-preview"><?=$ui_block->render('preview', array())?></li>
                                                        <? endif ?>
                                                    </ul>
                                                </li>
                                            <? endforeach?>
                                        <? endif ?>
                                        </ul>
                                    </li>
                                <? endforeach?>
                            </ul>
                        </li>
                    <? endforeach?>
                </ul>
            </li>
        <? endforeach?>
    </ul>
    <form class="blockmanager-form" id="blockmanager-store-changes" method="post" enctype="multipart/form-data">
        <input type="hidden" name="subcmd" value="store_changes">
        <input type="hidden" name="chapterList" id="chapterList" value="">
        <input type="hidden" name="subchapterList" id="subchapterList" value="">
        <input type="hidden" name="sectionList" id="sectionList" value="">
        <input type="hidden" name="blockList" id="blockList" value="">
        <input type="hidden" name="cid" value="<?= $cid ?>">
        <input type="hidden" name="importXML" id="importXML" value="">
        <input type="hidden" name="import" id="import" value=false>
        <input type="hidden" name="remote" id="remote" value=false>
        <? if ($show_remote_courseware): ?>
            <input type="hidden" name="remote_course_name" id="remote_course_name" value="<?= $remote_course_name?>">
        <? endif ?>
        <input type="file" name="cw-file-upload-import" class="cw-file-upload-import" id="cw-file-upload-import" accept=".zip">
        <button type="submit" class="button"><?= _cw('Änderungen speichern')?></button>
    </form>
    <div class="clear"></div>
</div>

<div id="cw-import-wrapper" class="cw-blockmanager-wrapper <? if ($show_remote_courseware):?> cw-blockmanager-remote-courseware<? endif?>">
    <input type="hidden" id="block_map" value='<?= $block_map?>'>
    <div id="cw-import-title" class="cw-blockmanager-title">
        <p><?= _cw('Import') ?><? if ($show_remote_courseware) echo ' - '.$remote_course_name?></p>
    </div>

    <div id="cw-import-lists">
    </div>
    <form class="blockmanager-form" id="cw-blockmanager-form-full-import" method="post" enctype="multipart/form-data">
        <input type="hidden" name="cid" value="<?= $cid ?>">
        <input type="hidden" name="subcmd" value="fullimport">
        <button type="submit" class="button"><?= _cw('Komplettes Archiv importieren') ?></button>
    </form>
    <? if ($show_remote_courseware):?>
        <div id="remote-courseware-list">
            <ul class="chapter-list chapter-list-import">
                <? foreach($remote_courseware['children'] as $chapter): ?>
                    <li class="chapter-item chapter-item-import chapter-item-remote" data-id="remote-<?= $chapter['id']?>">
                        <p class="chapter-description"><?= $chapter['title']?> 
                            <span>
                                <?= _cw('Kapitel') ?>
                                <? if($chapter['publication_date'] != null):?>
                                    | <?= _cw('veröffentlichen') ?>: <?=$chapter['publication_date']?>
                                <? endif ?>
                                <? if($chapter['withdraw_date'] != null):?>
                                    | <?= _cw('widerrufen') ?>: <?=$chapter['withdraw_date']?>
                                <? endif ?>
                                <? if(!$chapter['isPublished']):?><span class="structure-not-visible"></span><? endif?>
                            </span>
                        </p>
                        <ul class="subchapter-list subchapter-list-import">
                            <? foreach($chapter['children'] as $subchapter): ?>
                                <li class="subchapter-item subchapter-item-import subchapter-item-remote" data-id="remote-<?= $subchapter['id']?>">
                                    <p class="subchapter-description"><?= $subchapter['title'] ?>
                                        <span>
                                            <?= _cw('Unterkapitel') ?>
                                            <? if($subchapter['publication_date'] != null):?>
                                                | <?= _cw('veröffentlichen') ?>: <?=$subchapter['publication_date']?>
                                            <? endif ?>
                                            <? if($subchapter['withdraw_date'] != null):?>
                                                | <?= _cw('widerrufen') ?>: <?=$subchapter['withdraw_date']?>
                                            <? endif ?>
                                            <? if(!$subchapter['isPublished']):?><span class="structure-not-visible"></span><? endif?>
                                        </span>
                                    </p>
                                    <ul class="section-list section-list-import">
                                        <? foreach($subchapter['children'] as $section):?>
                                            <li class="section-item section-item-import section-item-remote" data-id="remote-<?= $section['id']?>">
                                                <p class="section-description" <? if(strlen($section['title'])>24): ?>title="<?= $section['title'] ?>"<?endif?>><?= substr($section['title'], 0, 24)?><? if(strlen($section['title'])>24): ?>…<?endif?> <span><?= _cw('Abschnitt') ?></span></p>
                                                <ul class="block-list block-list-import">
                                                    <? if($section['children'] != null):?>
                                                    <? foreach($section['children'] as $block):?>
                                                        <? $ui_block = $block['ui_block']?>
                                                        <li class="block-item block-item-import block-item-remote" data-id="remote-<?= $block['id']?>">
                                                            <p class="block-description"><span class="block-icon cw-block-icon-<?=$block['type']?>"></span><?= $ui_block::NAME ?><? if(!$block['visible']):?><span class="block-not-visible"></span><? endif?>
                                                            </p>
                                                            <ul class="block-preview">
                                                                <? if(method_exists($ui_block, 'preview_view')): ?>
                                                                    <li class="block-content-preview"><?=$ui_block->render('preview', array())?></li>
                                                                <? endif ?>
                                                            </ul>
                                                        </li>
                                                    <? endforeach?>
                                                    <? endif ?>
                                                </ul>
                                            </li>
                                        <? endforeach?>
                                    </ul>
                                </li>
                            <? endforeach?>
                        </ul>
                    </li>
                <? endforeach?>
            </ul>
        </div>
    <? endif?>
    <div id="user-course-list">
        <ul class="semester-list">
            <? if(!empty($remote_courses)):?>
                <? foreach ($remote_courses as $start_time => $sem_courses): ?>
                    <li class="semester-item">
                        <p class="semester-description"><?=  $start_time?></h1>
                        <ul class="course-list">
                            <? foreach ($sem_courses as $key => $value): ?>
                                <li id="<?=$key?>" class="course-item">
                                    <form class="blockmanager-form" method="post">
                                        <input type="hidden" name="subcmd" value="showRemoteCourseware">
                                        <input type="hidden" name="remote_course_id" value="<?= $key ?>">
                                        <button type="submit" class="blockmanager-course-item"><?= $value ?></button>
                                    </form>
                                </li>
                            <? endforeach; ?>
                        </ul>
                    </li>
                <? endforeach; ?>
            <? else: ?>
                <li><?= _cw('Es wurden keine weitere Veranstaltung gefunden in der Courseware aktiv ist')?>.</li>
            <? endif ?>
        </ul>
    </div>
    <ul id="cw-import-selection">
        <li>
            <label for="cw-file-upload-import" id="cw-file-upload-import-label" title="<?= _cw('Laden Sie eine Datei hoch, die Sie zuvor aus einer Courseware exportiert haben') ?>">
                <p><?= _cw('Import-Archiv hochladen') ?></p>
            </label>
        </li>

        <li>
            <div id="cw-import-from-course"  title="<?= _cw('Importieren Sie Inhalte aus einer anderen Veranstaltung in der Sie Dozent sind')?>">
                <p><?= _cw('Aus Veranstaltung importieren') ?></p>
            </div>
        </li>
    </ul>

    <div style="clear: both;"></div>

</div>
