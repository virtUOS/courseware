<ul>
    <? foreach($threads as $thread): ?>
    <li><?= $thread["thread_title"]?></li>
        <? if (array_key_exists($thread["thread_id"], ($thrads_in_blocks))) :?>
            <? foreach($thrads_in_blocks[$thread["thread_id"]] as $block): ?>
                <a href="<?= $block['link']?>">
                    <?= $block['title']?>
                </a>
                <br>
            <? endforeach; ?>
        <? else :?>
            <p>Dieser Thread wird in keinem Block genutzt.</p>
        <? endif; ?>

        <div class="cw-postblock-posts">
            <? $counter = 0;
                foreach($thread["posts"] as $post){
                    if (object_get_visit($_SESSION['SessionSeminar'], "courseware") < strtotime($post["mkdate"])) {
                        $counter++;
                    }
                }?>
            <? switch ($counter) {
                case 0:
                    echo "<p>Es gibt keine neuen Beiträge.</p>";
                    break;
                case 1:
                    echo "<p>Es gibt einen neuen Beitrag</p>";
                    break;
                default:
                    echo "<p>Es gibt " . $counter . " neue Beiträge</p>";
            }?>
        </div>
    <? endforeach;?>
</ul>
