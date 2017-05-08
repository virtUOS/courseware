<div class="cw-news">
    <h1>Neue Inhalte</h1>
    <ul class="cw-news-list">
        <? foreach ($new_ones as  $item):?>
            <? if ( (strpos($item["title"], "AsideSection") >-1) || (in_array($block->type , array("Chapter", "Subchapter"))) ): continue; endif; ?>
            <? $block = new Mooc\DB\Block($item["id"]); ?>
            <? if ($block->parent->parent->id):?>
            <li class="cw-news-item">
                <?= (new Mooc\DB\Block($block->parent->parent->parent->id))->title ?> &rarr; 
                <?= (new Mooc\DB\Block($block->parent->parent->id))->title ?> &rarr; 
                <a href="<?= \PluginEngine::getURL("courseware/courseware")."&selected=".$block->parent_id ?>"><?= $block->title; ?></a>
            </li>
             <? endif; ?>
        <? endforeach ?>
    </ul>
</div>

<style>
    .cw-news-list {
        margin-top: 0.5em;
        max-width: 75%;
    }

    .cw-news-item {
        list-style: none;
        margin-left: -3em;
        padding: 0.5em;
        padding-left: 1.5em;
        background: #eee url("../../../../assets/images/icons/16/blue/arr_1right.png") no-repeat 3px center;
    }

    .cw-news-item:nth-child(2n+1) {
        background: #f8f8f8 url("../../../../assets/images/icons/16/blue/arr_1right.png") no-repeat 3px center;
    }
</style>
