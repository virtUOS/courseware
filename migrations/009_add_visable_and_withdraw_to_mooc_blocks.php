<?php

/**
 * add withdraw_date and visible to mooc_blocks
 *
 * @author <rlucke@uos.de>
 */


class AddVisableAndWithdrawToMoocBlocks extends Migration
{
    public function description()
    {
        return 'add withdraw_date and visible to mooc_blocks';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("
           ALTER TABLE `mooc_blocks` ADD `withdraw_date` int(11) DEFAULT NULL AFTER `publication_date`;
        ");

        $db->exec("
           ALTER TABLE `mooc_blocks` ADD `visible` BOOLEAN NOT NULL DEFAULT TRUE AFTER `position`;
        ");

        SimpleORMap::expireTableScheme();
    }

    function down()
    {
    }
}
