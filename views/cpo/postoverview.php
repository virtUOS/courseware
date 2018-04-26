<? if (count($threads) != 0): ?>
    <ul class="post-overview">
        <? foreach($threads as $thread): ?>
            <? $counter = 0;
            foreach($thread["thread_posts"]["posts"] as $post){
                if (object_get_visit($_SESSION['SessionSeminar'], "courseware") < strtotime($post["mkdate"])) {
                    $counter++;
                }
            }?>
            <li id="container_<?= $thread["thread_id"]?>" class="post-overview-thread <?if ($counter > 0):?> post-overview-highlight <?endif;?>">
                <h3 class="thread-title">
                    <span class="thread-title-content">
                        <?= $thread["thread_title"]?> (id = <?= $thread["thread_id"]?>)
                        <span class="edit-thread-title-button"><?= Icon::create('edit', 'clickable'); ?></span>
                    </span>
                    <form class="edit-thread-title" action="edit_title" style="display:none;">
                        <input type="hidden" value="<?= $thread["thread_id"]?>" name="thread_id">
                        <input type="text" value="<?= $thread["thread_title"]?>" name="thread_title">
                        <input type="hidden" name="cid" value="<?= $cid ?>">
                        <button class="edit-thread-button" type="submit"> <?= Icon::create('accept', 'accept') ?> </button>
                        <button class="edit-thread-button edit-reset" type="reset"> <?= Icon::create('decline', 'status-red') ?> </button>
                    </form>
                </h3>

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
                <div class="talk-bubble <?if ($post["own_post"]):?>own-<?endif;?>post <?if ($post["hidden"]):?>hidden-post<?endif;?>">
                    <? if ($post["hidden"]): ?>
                        <div class="post-is-hidden-info">
                            <p><?= _cw('Beitrag ist ausgeblendet!') ?></p>
                        </div>
                    <? endif; ?>
                    <? if (!$post["own_post"]): ?>
                    <div class="post-user">
                        <?= $post["avatar"]?>
                        <p><?= $post["user_name"]?></p>
                    </div>
                    <? endif; ?>
                  <div class="talktext">
                     <p><?= $post["content"]?></p>
                    <p class="talktext-time">
                        <?= $post["date"]?>
                        <form class="" action="hide_post" method="get">
                            <input type="hidden" name="thread_id" value="<?= $thread["thread_id"]?>">
                            <input type="hidden" name="post_id"  value="<?= $post["post_id"]?>">
                            <input type="hidden" name="cid" value="<?= $cid ?>">
                            <? if ($post["hidden"]): ?>
                                <input type="hidden" name="hide_post"  value="0">
                                <input type="submit" value="einblenden" class="button post-show">
                            <? else: ?>
                                <input type="hidden" name="hide_post"  value="1">
                                <input type="submit" value="ausblenden" class="button post-hide">
                            <? endif; ?>
                        </form>
                    </p>
                  </div>
                </div>
                <? endforeach; ?>
                <div class="clear"></div>
            </div>
        <? endforeach;?> 
    </div>
    <form class="postoverview-form" action="answer" method="get">
        <input type="hidden" name="thread_id" id="input_thread_id" value="">
        <input type="hidden" name="cid" value="<?= $cid ?>">
        <textarea name="content" placeholder="<?= _cw('Auf Beitrag antworten...') ?>" spellcheck="true"></textarea>
        <input type="submit" value="senden" class="button">
    </form>
<? else: ?>
    <div class="post-overview-no-threads"> 
        <div class="messagebox messagebox_info"><?= _cw('Die Courseware in dieser Veranstaltung enthält noch keine Diskussion.') ?></div>
    </div>
<? endif ?>
