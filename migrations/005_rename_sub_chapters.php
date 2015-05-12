<?php

class RenameSubChapters extends Migration
{
    function description()
    {
        return 'Renames all former SubChapters to Subchapter';
    }

    function up()
    {
        $db = DBManager::get();
        $db->exec('UPDATE `mooc_blocks` SET `type` = "Subchapter" WHERE `type` = "SubChapter"');
    }

    function down()
    {
        // nothing to do
    }
}
