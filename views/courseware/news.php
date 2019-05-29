<div class="cw-news">
    <h1><?= _cw('Letzte Änderungen') ?></h1>
    <ul class="cw-news-list-sections">
    <? foreach ($new_content as $chapter_title => $chapter_content):?>
        <? foreach ($chapter_content as $subchapter_title => $subchapter_content):?>
            <? foreach ($subchapter_content as $section_title => $section_content):?>
                <li class="cw-news-item-section">
                <h2><?= $chapter_title.'&rarr;'.$subchapter_title.'&rarr;'.$section_title ?></h2>
                    <ul class="cw-news-list-blocks">
                    <? foreach ($section_content as $block_id => $block):?>
                        <li class="cw-news-item-block">
                            <? $ui_block = $block['ui_block']; ?>
                            <a href="<?= \PluginEngine::getURL("courseware/courseware")."&selected=".$block['id'] ?>">
                                <h4 class="cw-block-title type-<?= $block['type']?>"><?= $block['title'] ?></h4>
                            </a>
                            <div><?= $ui_block->render('preview', array()) ?></div>
                        </li>
                    <? endforeach ?>
                    </ul>
                    <div style="clear: both"></div>
                </li>
            <? endforeach ?>
        <? endforeach ?>
    <? endforeach ?>
    </ul>
</div>