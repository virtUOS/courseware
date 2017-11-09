<ul class="post-overview">
    <? foreach($threads as $thread): ?>
    <li class="post-overview-thread">
        <h3><?= $thread["thread_title"]?></h3>
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
                echo "<p><b>Es gibt einen neuen Beitrag</b></p>";
                break;
            default:
                echo "<p><b>Es gibt " . $counter . " neue Beiträge</b></p>";
        }?>
        </li>
    <? endforeach;?>
</ul>
<style>
    .post-overview-thread {
        list-style: none;
        border: solid thin #ccc;
        width: 70%;
        padding: 0.5em 1em;  
        margin-bottom: 1em;
    }
    .post-overview-thread > h3 {
        
    }
    .post-overview-block-link{
        float: left;
        padding: 0.5em 0.5em 0.5em 1em;
        margin-right: 1em;
        background-image: url("../../../assets/images/icons/blue/arr_1right.svg");
        background-repeat: no-repeat;
        background-position-y: center;
    }
</style>
