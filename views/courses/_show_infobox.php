<table class="infobox" width="250" cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td class="infoboxrahmen">
                <table id="infobox_content" cellspacing="0" cellpadding="4">
                    <tbody>
                        <tr>
                            <td>
                                <? if ($preview_video) : ?>
                                <video src="<?= $preview_video ?>" style="width:240px;" poster="<?= $preview_image ?>">
                                </video>
                                <? else : ?>
                                    <img src="<?= $preview_image ?>">
                                <? endif ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>


