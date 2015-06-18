<?php
$discussions = array();

foreach ($blocks as $block ) {

    $ancestors = $block->getAncestors();
    $section = end($ancestors);

    // a real section with subchapter parent
    if ($section->parent_id != NULL) {

        // do not show courseware
        array_shift($ancestors);

        $breadcrumbs = join(" » ", array_map(function ($b) { return htmlReady($b->title); }, $ancestors));
        $order = array_map(function ($b) { return (int) $b->position; }, $ancestors);
        $url = $controller->url_for('courseware/index', array('selected' => $block->id));

    }

    // a section displayed in the aside
    else {
        // TODO: gruseliger Hack, um das Unter/Kapitel zu finden, in dem die Section eingehängt ist.
        $field = current(\Mooc\DB\Field::findBySQL('user_id = "" AND name = "aside_section" AND json_data = ?', array(json_encode($section->id))));
        $parent_block = $field->block;

        // now we can find the ancestors and
        // put the containing block into it too
        $ancestors = $parent_block->getAncestors();
        $ancestors[] = $parent_block;

        // do not show courseware
        array_shift($ancestors);

        $breadcrumbs = join(" » ", array_map(function ($b) { return htmlReady($b->title); }, $ancestors)) . " » Seitenblock";
        $order = array_map(function ($b) { return (int) $b->position; }, $ancestors);
        $url = $controller->url_for('courseware/index', array('selected' => $parent_block->id));
    }

    // create html
    $ui_block = $container['block_factory']->makeBlock($block);
    $html = $ui_block->render('student', array());


    $discussions[] = compact(words('order breadcrumbs url block html'));
}

$recursive_cmp = function ($ary1, $ary2)
{
    for ($i = 0, $len = min(sizeof($ary1), sizeof($ary2)); $i < $len; $i++) {
        if ($ary1[$i] === $ary2[$i]) {
            continue;
        }
        return $ary1[$i] < $ary2[$i] ? -1 : 1;
    }

    $d_len = sizeof($ary1) - sizeof($ary2);

    return $d_len === 0 ? 0 : ($d_len > 0 ? 1 : -1);
};

usort($discussions, function ($d1, $d2) use ($recursive_cmp) { return $recursive_cmp($d1['order'], $d2['order']);});
?>

<h1>
    Kurskommunikation
</h1>
<? foreach ($discussions as $discussion): ?>

    <section class="contentbox">

        <header>
            <h1>
                <?= htmlReady($discussion['breadcrumbs']) ?>
                <!-- <a href="<?= $discussion['url'] ?>"> <?= htmlReady($discussion['breadcrumbs']) ?> </a> -->
            </h1>
        </header>

        <section id=block-<?= $discussion['block']->id ?>
                 class="block <?= $discussion['block']->type ?>"
                 data-blockid="<?= $discussion['block']->id ?>"
                 data-blocktype="<?= $discussion['block']->type ?>">

            <div class="block-content" data-view="student">
                <?= $discussion['html'] ?>
            </div>
        </section>


    </section>
<? endforeach ?>

<?= $this->render_partial('courseware/_requirejs', array('main' => 'main-discussions')) ?>


<?
