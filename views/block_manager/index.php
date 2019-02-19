<? $body_id = 'courseware-blockmanager'; ?>
<div class="cw-blockmanager-wrapper" style="border: solid thin #28497c;">
    <div class="cw-blockmanager-title">
        <p><?= _cw('Block Manager') ?></p>
        <form class="blockmanager-form" action="block_manager/store_changes" method="get">
            <input type="hidden" name="chapterList" id="chapterList" value="">
            <input type="hidden" name="subchapterList" id="subchapterList" value="">
            <input type="hidden" name="sectionList" id="sectionList" value="">
            <input type="hidden" name="blockList" id="blockList" value="">
            <input type="hidden" name="cid" value="<?= $cid ?>">
            <input type="file"   name="cw-file-upload-import" id="cw-file-upload-import"  accept=".zip" style="display: none;">
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
<br>
<div id="cw-import-wrapper">
    <p id="cw-import-title">Import</p>
    <div id="cw-import-lists">
    </div>

    <div id="cw-import-selection">
        <label for="cw-file-upload-import" id="cw-file-upload-import-label">
            <p>Datei für den Import wählen</p>
        </label>
    </div>
</div>
