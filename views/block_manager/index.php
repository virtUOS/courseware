<? $body_id = 'courseware-blockmanager'; ?>
<h1><?= _cw('Block Manager') ?></h1>
<form class="blockmanager-form" action="block_manager/store_changes" method="get">
    <input type="hidden" name="chapterList" id="chapterList" value="">
    <input type="hidden" name="subchapterList" id="subchapterList" value="">
    <input type="hidden" name="sectionList" id="sectionList" value="">
    <input type="hidden" name="blockList" id="blockList" value="">
    <input type="hidden" name="cid" value="<?= $cid ?>">
    <button type="submit" class="button">Änderungen speichern</button>
</form>
<div class="clear"></div>
<ul class="chapter-list">
    <? foreach($courseware['children'] as $chapter): ?>
        <li class="chapter-item" data-id="<?= $chapter['id']?>">
            <p class="chapter-description"><?= $chapter['title']?> <span>Kapitel</span></p>
            <ul class="subchapter-list">
                <? foreach($chapter['children'] as $subchapter): ?>
                    <li class="subchapter-item" data-id="<?= $subchapter['id']?>">
                        <p class="subchapter-description"><?= $subchapter['title'] ?> <span>Unterkapitel</span></p>
                        <ul class="section-list">
                            <? foreach($subchapter['children'] as $section):?>
                                <li class="section-item" data-id="<?= $section['id']?>">
                                    <p class="section-description"><?= $section['title']?> <span>Abschnitt</span></p>
                                    <ul class="block-list">
                                    <? foreach($section['children'] as $block):?>
                                        <li class="block-item cw-block-icon-<?=$block['type']?>" data-id="<?= $block['id']?>"><?= $block['type'] ?></li>
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