<ul class="post-overview">
    <? foreach($threads as $thread): ?>
        <? $counter = 0;
        foreach($thread["thread_posts"]["posts"] as $post){
            if (object_get_visit($_SESSION['SessionSeminar'], "courseware") < strtotime($post["mkdate"])) {
                $counter++;
            }
        }?>
        <li class="post-overview-thread <?if ($counter > 0):?> post-overview-highlight <?endif;?>">
            <h3><?= $thread["thread_title"]?> (id = <?= $thread["thread_id"]?>)</h3>
            <? if (array_key_exists($thread["thread_id"], ($thrads_in_blocks))) :?>
                <? foreach($thrads_in_blocks[$thread["thread_id"]] as $block): ?>
                    <p class="post-overview-block-link"><a href="<?= $block['link']?>">
                        <?= $block['title']?>
                    </a>
                    </p>
                <? endforeach; ?>
            <? else :?>
                <p>Dieser Thread wird in keinem Block genutzt.</p>
            <? endif; ?>
            <div class="clear"></div>

            <? switch ($counter) {
                case 0:
                    echo "<p>Es gibt keine neuen Beiträge.</p>";
                    break;
                case 1:
                    echo "<p><b>Es gibt einen neuen Beitrag</b></p>";
                    break;
                default:
                    echo "<p><b>Es gibt " . $counter . " neue Beiträge</b></p>";
            }?>
            <button class="button show-thread-button" name="show_thread_<?= $thread['thread_id']?>" data-showthread="<?= $thread['thread_id']?>">Beiträge anzeigen</button>
        </li>
    <? endforeach;?>
</ul>
<div class="post-overview-postings">
    <? foreach($threads as $thread): ?>
        <div class="thread" id="thread_<?= $thread["thread_id"]?>">
            <h3><?= $thread["thread_title"]?></h3>
            <? foreach ($thread["thread_posts"]["posts"] as $post): ?>
            <div class="talk-bubble <?if ($post["own_post"]):?>own-<?endif;?>post">
                <?if (!$post["own_post"]):?>
                <div class="post-user">
                    <?= $post["avatar"]?>
                    <p><?= $post["user_name"]?></p>
                </div>
                <?endif;?>
              <div class="talktext">
                 <p><?= $post["content"]?></p>
                <p class="talktext-time"><?= $post["date"]?></p>
              </div>
            </div>
               
            <? endforeach; ?>
            <div class="clear"></div>
        </div>
    <? endforeach;?> 
</div>
<script>
    $('.show-thread-button').click(function(event){
        $('.thread').hide();
        $('#thread_'+$(event.target).data('showthread')).show();
    });
</script>
