<?php

class AddApprovalToMoocBlocks extends Migration
{
    public function description()
    {
        return 'adds a coloum for user editing permissions on chapters and subchapters to mooc_blocks table';
    }

    public function up()
    {
        $db = DBManager::get();
        $db->exec("
            ALTER TABLE `mooc_blocks` ADD `approval` mediumtext NOT NULL AFTER `visible`;
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();
        $db->exec("
            ALTER TABLE `mooc_blocks` DROP COLUMN `approval`;
        ");
        SimpleORMap::expireTableScheme();
    }
}