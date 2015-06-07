<h1>
    Kurskommunikation
</h1>

<? foreach ($blocks as $block ) : ?>

    <?
    $ancestors = $block->getAncestors();
    $section = end($ancestors);

    if ($section->parent_id != NULL) {
        $url = $controller->url_for('courseware/index', array('selected' => $block->id));

        array_shift($ancestors);
        $link = join(" » ", array_map(function ($b) { return htmlReady($b->title); }, $ancestors));

    } else {
        // TODO: gruseliger Hack
        $field = current(\Mooc\DB\Field::findBySQL('user_id = "" AND name = "aside_section" AND json_data = ?', array(json_encode($section->id))));
        $parent_block = $field->block;
        $url = $controller->url_for('courseware/index', array('selected' => $parent_block->id));

        $ancestors = $parent_block->getAncestors();
        array_shift($ancestors);
        $link = join(" » ", array_map(function ($b) { return htmlReady($b->title); }, $ancestors)) . " » Seitenblock";
    }

    $ui_block = $container['block_factory']->makeBlock($block);
    $html = $ui_block->render('student', array());
    ?>

    <section class="contentbox">

        <header>
            <h1>
                <?= htmlReady($link) ?>
                <!-- <a href="<?= $url ?>"> <?= htmlReady($link) ?> </a> -->
            </h1>
        </header>

        <section id=block-<?= $block->id ?>
                 class="block <?= $block->type ?>"
                 data-blockid="<?= $block->id ?>"
                 data-blocktype="<?= $block->type ?>">

            <div class="block-content" data-view="student">
                <?= $html ?>
            </div>
        </section>


    </section>
<? endforeach ?>

<?= $this->render_partial('courseware/_requirejs', array('main' => 'main-discussions')) ?>
