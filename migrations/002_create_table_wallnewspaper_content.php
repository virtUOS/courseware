<?php
class CreateTableWallnewspaperContent extends DBMigration {

    public function description () {
        return 'Setup table for the WallNewspaper block.';
    }

    public function up () {

        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `cw_wallnewspaper_content` (
          `id`       int(11) NOT NULL AUTO_INCREMENT,
          `block_id` int(11) DEFAULT NULL,
          `topic_id` varchar(32) DEFAULT NULL,
          `video`    mediumtext,
          `text`     mediumtext,
          `chdate`   int(11) DEFAULT NULL,
          `mkdate`   int(11) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `block_id` (`block_id`, `topic_id`)
        )");

        SimpleORMap::expireTableScheme();
    }

    public function down ()
    {
        DBManager::get()->exec("DROP TABLE cw_wallnewspaper_content");

        SimpleORMap::expireTableScheme();
    }
}
