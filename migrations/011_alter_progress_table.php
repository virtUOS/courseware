<?php
class AlterProgressTable extends DBMigration {

    public function description () {
        return 'create table for user progress';
    }

    public function up () {
        $db = DBManager::get();
        $db->exec('ALTER TABLE `mooc_userprogress` DROP `json_data`');
        SimpleORMap::expireTableScheme();
    }

    public function down () {
        $db = DBManager::get();
        $db->exec("ALTER TABLE `mooc_userprogress` ADD `json_data` MEDIUMTEXT NULL COMMENT 'JSON'");
        SimpleORMap::expireTableScheme();
    }
}
