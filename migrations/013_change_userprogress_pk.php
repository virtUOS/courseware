<?php
class ChangeUserprogressPk extends DBMigration {

    public function description () {
        return 'Changes userprogress\'s pk.';
    }

    public function up () {
        $db = DBManager::get();
        $db->exec('ALTER TABLE `mooc_userprogress` DROP `id`');
        $db->exec('ALTER TABLE `mooc_userprogress` ADD PRIMARY KEY (`block_id`, `user_id`)');
        SimpleORMap::expireTableScheme();
    }

    public function down () {
        $db = DBManager::get();
        $db->exec('ALTER TABLE `mooc_userprogress` DROP PRIMARY KEY');
        $db->exec('ALTER TABLE `mooc_userprogress` ADD `id` INT NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`)');
        SimpleORMap::expireTableScheme();
    }
}
