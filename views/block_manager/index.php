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
    echo'<div class="cw-blockmanager-title"><p>'._cw('Warnung! Es konnten nicht alle Blöcke importiert werden.').'</p></div>';
    echo '<ul class="cw-blockmanager-info-content">';
    foreach ($warnings as $warning):
        echo '<li>'.htmlReady($warning).'</li>';
    endforeach;
    echo '</ul>';
    echo"<p><b>"._cw("Bitte überprüfen Sie den Inhalt Ihrer Courseware und den Daten in Ihrer Importdatei.")."</b></p>";
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
        <form class="blockmanager-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="subcmd" value="store_changes">
            <input type="hidden" name="chapterList" id="chapterList" value="">
            <input type="hidden" name="subchapterList" id="subchapterList" value="">
            <input type="hidden" name="sectionList" id="sectionList" value="">
            <input type="hidden" name="blockList" id="blockList" value="">
            <input type="hidden" name="cid" value="<?= $cid ?>">
            <input type="hidden" name="importXML" id="importXML" value="">
            <input type="hidden" name="import" id="import" value=false>
            <input type="file" name="cw-file-upload-import" class="cw-file-upload-import" id="cw-file-upload-import" accept=".zip">
            <button type="submit" class="button">Änderungen speichern</button>
        </form>
        <div class="clear"></div>

    </div>

    <ul class="chapter-list">
        <? foreach($courseware['children'] as $chapter): ?>
            <li class="chapter-item" data-id="<?= $chapter['id']?>">
                <p class="chapter-description"><?= $chapter['title']?> 
                    <span>
                        Kapitel
                        <? if($chapter['publication_date'] != null):?>
                            | publication: <?=$chapter['publication_date']?>
                        <? endif ?>
                        <? if($chapter['withdraw_date'] != null):?>
                            | withdraw: <?=$chapter['withdraw_date']?>
                        <? endif ?>
                        <? if(!$chapter['isPublished']):?><span class="structure-not-visible"></span><? endif?>
                    </span>
                </p>
                <ul class="subchapter-list">
                    <? foreach($chapter['children'] as $subchapter): ?>
                        <li class="subchapter-item" data-id="<?= $subchapter['id']?>">
                            <p class="subchapter-description"><?= $subchapter['title'] ?>
                                <span>
                                    Unterkapitel
                                    <? if($subchapter['publication_date'] != null):?>
                                        | publication: <?=$subchapter['publication_date']?>
                                    <? endif ?>
                                    <? if($subchapter['withdraw_date'] != null):?>
                                        | withdraw: <?=$subchapter['withdraw_date']?>
                                    <? endif ?>
                                    <? if(!$subchapter['isPublished']):?><span class="structure-not-visible"></span><? endif?>
                                </span>
                            </p>
                            <ul class="section-list">
                                <? foreach($subchapter['children'] as $section):?>
                                    <li class="section-item" data-id="<?= $section['id']?>">
                                        <p class="section-description"><?= $section['title']?> <span>Abschnitt</span></p>
                                        <ul class="block-list">
                                        <? foreach($section['children'] as $block):?>
                                            <? $ui_block = $block['ui_block']?>
                                            <li class="block-item" data-id="<?= $block['id']?>">
                                                <p class="block-description cw-block-icon-<?=$block['type']?>">
                                                    <?= $ui_block::NAME ?><? if(!$block['visible']):?><span class="block-not-visible"></span><? endif?>
                                                </p>
                                                <ul class="block-preview">
                                                    <li class="block-id">ID: <?=$block['id']?></li>
                                                    <? if(method_exists($ui_block, 'preview_view')): ?>
                                                        <li class="block-content-preview"><?=$ui_block->render('preview', array())?></li>
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
            </li>
        <? endforeach?>
    </ul>
</div>

<div id="cw-import-wrapper" class="cw-blockmanager-wrapper">
    <div id="cw-import-title" class="cw-blockmanager-title">
        <p>Import</p>
        <form class="blockmanager-form" id="cw-blockmanager-form-full-import" method="post" enctype="multipart/form-data">
            <input type="hidden" name="cid" value="<?= $cid ?>">
            <input type="hidden" name="subcmd" value="fullimport">
            <button type="submit" class="button">Komplettes Archiv importieren</button>
        </form>
    </div>

    <div id="cw-import-lists">
    </div>

    <div id="cw-import-selection">
        <label for="cw-file-upload-import" id="cw-file-upload-import-label">
            <p>Datei für den Import wählen</p>
        </label>
    </div>
</div>

<div class="cw-blockmanager-wrapper">
    <div class="cw-blockmanager-title" id="cw-export-title">
        <p>Export</p>
        <form class="blockmanager-form" id="cw-blockmanager-form-export" action="block_manager/export" method="post" enctype="multipart/form-data">
            <input type="hidden" name="cid" value="<?= $cid ?>">
            <input type="hidden" name="subcmd" value="fullimport">
            <button type="submit" class="button">Komplettes Archiv exportieren</button>
        </form>
    </div>
</div>
