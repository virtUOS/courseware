<?php

/**
 * Setup table mooc_posts for the new PostBlock.
 *
 * @author <rlucke@uos.de>
 */


class AddTableMoocPosts extends Migration
{
    public function description()
    {
        return 'Setup table mooc_posts for the new PostBlock';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `mooc_posts` (
          `thread_id` int(11) NOT NULL,
          `post_id` int(4) NOT NULL,
          `seminar_id` varchar(32) NOT NULL,
          `user_id` varchar(32) NOT NULL,
          `content` text NOT NULL,
          `mkdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `chdate` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`thread_id`,`post_id`, `seminar_id`)
        )");

        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        // To avoid data loss, nothing is deleted by default
        // remove the following "return;"-statement to clean tables on uninstall
        return;

        DBManager::get()->exec("DROP TABLE mooc_posts");

        SimpleORMap::expireTableScheme();
    }
}

