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

        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();
        $db->exec("ALTER TABLE `mooc_blocks` ADD `json_data` MEDIUMTEXT NULL COMMENT 'JSON'");

        SimpleORMap::expireTableScheme();
    }
}
