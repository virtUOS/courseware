<table class="infobox" width="251" cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td class="infoboxrahmen">
                <table id="infobox_content" cellspacing="0" cellpadding="4">
                    <tbody>
                        <tr>
                            <td>
                                <? if ($preview_video) : ?>
                                <video src="<?= $preview_video ?>" style="width:240px;" poster="<?= $preview_image ?>" controls>
                                </video>
                                <? else : ?>
                                    <img src="<?= $preview_image ?>">
                                <? endif ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p style="margin-top:3em; margin-bottom:6em;">
                                <em>TODO: Hier kommen noch weitere Infos zum Kurs hin...</em>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>

