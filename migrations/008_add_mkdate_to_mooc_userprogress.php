<?php

/**
 * add mkdate to userprogress
 *
 * @author <rlucke@uos.de>
 */


class AddMkdateToMoocUserprogress extends Migration
{
    public function description()
    {
        return 'add mkdate to mooc_userprogress';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("
           ALTER TABLE `mooc_userprogress` ADD `mkdate` DATETIME NOT NULL AFTER `chdate`;
        ");

        $db->exec("
           UPDATE `mooc_userprogress` SET `mkdate` = `chdate`;
        ");

        SimpleORMap::expireTableScheme();
    }

    function down()
    {
    }
}
