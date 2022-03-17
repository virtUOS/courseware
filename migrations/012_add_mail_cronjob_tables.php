<?php

/**
 * Setup table mooc_posts for the new PostBlock.
 *
 * @author    Ron Lucke <lucke@elan-ev.de>
 */


class AddMailCronjobTables extends Migration
{
    public function description()
    {
        return 'Setup table mooc_mail_log';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `mooc_maillog` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `seminar_id` varchar(32) NOT NULL,
          `user_id` varchar(32) NOT NULL,
          `mail_type` varchar(32) NOT NULL,
          `mkdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `chdate` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`)
        )");

        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        // To avoid data loss, nothing is deleted by default
        // remove the following "return;"-statement to clean tables on uninstall
        return;
        DBManager::get()->exec("DROP TABLE mooc_mail_log");

        SimpleORMap::expireTableScheme();

        
    }
}

