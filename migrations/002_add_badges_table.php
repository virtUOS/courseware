<?php
class AddBadgesTable extends DBMigration {

    public function description () {
        return 'create tables for badges achieved by users';
    }

    public function up () {
        $db = DBManager::get();
        $db->exec("CREATE  TABLE `mooc_badges` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `sem_id` VARCHAR(32) NOT NULL,
            `user_id` VARCHAR(32) NOT NULL,
            `badge_block_id` VARCHAR(32) NOT NULL,
            `mkdate` INT(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        )");
		
        SimpleORMap::expireTableScheme();
    }

    public function down () {
        DBManager::get()->exec("DROP TABLE mooc_badges");
        SimpleORMap::expireTableScheme();
    }
}
