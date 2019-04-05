<?php

/**
 * Enhance performance by using index
 *
 * @author <rlucke@uos.de>
 */


class AddIndexToMoocBlocks extends Migration
{
    public function description()
    {
        return 'adds an index to mooc_blocks parent_id';
    }

    public function up()
    {
        $db = DBManager::get();        

        $db->exec("
           ALTER TABLE `mooc_blocks` ADD INDEX `seminar_id+type` (`seminar_id`, `type`);
        ");
        $db->exec("
            ALTER TABLE `mooc_blocks` ADD INDEX (`parent_id`);
        ");

        SimpleORMap::expireTableScheme();
    }

    function down()
    {
    }
}
