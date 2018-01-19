<ul class="post-overview">
    <? foreach($threads as $thread): ?>
        <? $counter = 0;
        foreach($thread["thread_posts"]["posts"] as $post){
            if (object_get_visit($_SESSION['SessionSeminar'], "courseware") < strtotime($post["mkdate"])) {
                $counter++;
            }
        }?>
        <li id="container_<?= $thread["thread_id"]?>" class="post-overview-thread <?if ($counter > 0):?> post-overview-highlight <?endif;?>">
            <h3><?= $thread["thread_title"]?> (id = <?= $thread["thread_id"]?>)</h3>
            <? if (array_key_exists($thread["thread_id"], ($thrads_in_blocks))) :?>
                <? foreach($thrads_in_blocks[$thread["thread_id"]] as $block): ?>
                    <p class="post-overview-block-link"><a href="<?= $block['link']?>">
                        <?= $block['title']?>
                    </a>
                    </p>
                <? endforeach; ?>
            <? else :?>
                <p><?= _cw('Dieser Thread wird in keinem Block genutzt.') ?></p>
            <? endif; ?>
            <div class="clear"></div>

            <? switch ($counter) {
                case 0:
                    echo '<p>' . _cw('Es gibt keine neuen Beiträge.') . '</p>';
                    break;
                case 1:
                    echo '<p><b>' . _cw('Es gibt einen neuen Beitrag') . '</b></p>';
                    break;
                default:
                    echo '<p><b>' . _cw('Es gibt ') . $counter . _cw(' neue Beiträge') . '</b></p>';
            }?>
            <button class="button show-thread-button" name="show_thread_<?= $thread['thread_id']?>" data-showthread="<?= $thread['thread_id']?>"><?= _cw('Beiträge anzeigen') ?></button>
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
<form class="postoverview-form" action="answer" method="get">
    <input type="hidden" name="thread_id" id="input_thread_id" value="">
    
    <textarea name="content" placeholder="<?= _cw('Auf Beitrag antworten...') ?>" spellcheck="true"></textarea>
    <input type="submit" value="senden" class="button">
</form>
<script>
    var thread_id_from_url = location.search.split('thread_id=')[1];
    if(thread_id_from_url){
        $('#thread_'+thread_id_from_url).show();
        $('.post-overview-postings').scrollTop($('.post-overview-postings')[0].scrollHeight);
        $('.post-overview').scrollTop(
            $('#container_'+thread_id_from_url).offset().top - $('.post-overview').offset().top + $('.post-overview').scrollTop()
        );
    }
    $('.show-thread-button').click(function(event){
        var $thread_id = $(event.target).data('showthread');
        $('.thread').hide();
        $('#thread_'+$thread_id).show();
        $('.post-overview-postings').scrollTop($('.post-overview-postings')[0].scrollHeight);
        $('#input_thread_id').val($thread_id);
    });
</script>
