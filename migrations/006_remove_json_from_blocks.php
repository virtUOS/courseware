<?php

class RemoveJsonFromBlocks extends Migration
{
    function description()
    {
        return 'Remove the field `json_data` from table `mooc_blocks`';
    }

    function up()
    {
        $db = DBManager::get();
        $db->exec('ALTER TABLE `mooc_blocks` DROP `json_data`');
    }

    function down()
    {
        $db = DBManager::get();
        $db->exec("ALTER TABLE `mooc_blocks` ADD `json_data` MEDIUMTEXT NULL COMMENT 'JSON'");
    }
}
