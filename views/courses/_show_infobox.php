<table class="infobox" width="251" cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td class="infoboxrahmen">
                <table id="infobox_content" cellspacing="0" cellpadding="4">
                    <tbody>
                        <tr>
                            <td style="cursor: pointer" id="preview_video">
                                <img src="<?= $preview_image ?: CourseAvatar::getAvatar($course->id)->getURL(Avatar::NORMAL) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Start: <?= strftime('%x', strtotime($start)) ?><br>
                                <br>
                                Dauer: <?= $duration ?><br>
                                <br>
                                <?= formatReady($hint) ?>
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
