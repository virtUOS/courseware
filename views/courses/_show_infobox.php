<?php
/** @var string $preview_image */
/** @var string $preview_video */
/** @var string $start */
/** @var string $duration */
/** @var string $hint */
?>

<table class="infobox" width="251" cellspacing="0" cellpadding="0">
    <tbody>
    <tr>
        <td class="infoboxrahmen">
            <table id="infobox_content" cellspacing="0" cellpadding="4">
                <tbody>
                <tr>
                    <td<?=$preview_video ? 'style="cursor: pointer"' : ''?> id="preview_video">
                        <div id="preview_image_container">
                            <img src="<?= $preview_image ?: CourseAvatar::getAvatar($course->id)->getURL(Avatar::NORMAL) ?>">

                            <?php
                            if ($preview_video):
                                echo '<div id="play_image"></div>';
                            endif;
                            ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php
                        if ($start):
                            echo 'Start: '.strftime('%x', strtotime($start));
                        endif;

                        if ($duration):
                            echo '<br>';
                            echo 'Dauer: '.$duration.'<br>';
                        endif;

                        if ($hint):
                            echo formatReady($hint);
                        endif;
                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>


<? if ($preview_video) : ?>

    <div id="videobox" style="display: none;">
        <iframe src="<?= $preview_video ?>" scrolling="no" allowfullscreen></iframe>
    </div>
<? endif ?>
