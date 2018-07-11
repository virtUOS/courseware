<div class="cw-news">
    <h1><?= _cw('Neue Inhalte') ?></h1>
    <ul class="cw-news-list">
        <? $newsCounter = 0;?>
        <? foreach ($new_ones as $item):?>
            <? $block = new Mooc\DB\Block($item["id"]); ?>
            <? // get readable name
                $class_name = 'Mooc\UI\\'.$block->type.'\\'.$block->type; 
                $name_constant = $class_name.'::NAME';

                if (defined($name_constant)) {
                    $title = _cw(constant($name_constant));
                } else {
                    $title = $block->title;
                }
            ?>
            <? if ( (strpos($item["title"], "AsideSection") >-1) || (in_array($block->type , array("Chapter", "Subchapter"))) ): continue; endif; ?>
            <? if ($block->parent->parent->id):?>
            <li class="cw-news-item">
                <?= (new Mooc\DB\Block($block->parent->parent->parent->id))->title ?> &rarr; 
                <?= (new Mooc\DB\Block($block->parent->parent->id))->title ?> &rarr; 
                <a href="<?= \PluginEngine::getURL("courseware/courseware")."&selected=".$block->id ?>"><?= $title; ?></a>
            </li>
            <? $newsCounter++;?>
             <? endif; ?>
        <? endforeach ?>
    </ul>
    <? if ($newsCounter == 0): ?>
        <?= _cw('Es wurden keine neuen Inhalte seit Ihrem letzten Besuch erstellt.') ?>
    <? endif?> 
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
        background: #eee url("../../../../assets/images/icons/blue/arr_1right.svg") no-repeat 3px center;
    }

    .cw-news-item:nth-child(2n+1) {
        background: #f8f8f8 url("../../../../assets/images/icons/blue/arr_1right.svg") no-repeat 3px center;
    }
</style>
