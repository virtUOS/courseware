<?php
class AddMoocipTables extends DBMigration {

    public function description () {
        return 'create tables for the mooc-plugin';
    }

    public function up () {
        $db = DBManager::get();
        $db->exec("CREATE  TABLE `mooc_blocks` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(64) NULL ,
            `parent_id` INT NULL ,
            `seminar_id` VARCHAR(32) NULL ,
            `title` VARCHAR(255) NULL ,
            `position` INT NULL DEFAULT 0 ,
            `json_data` MEDIUMTEXT NULL COMMENT 'JSON' ,
            `chdate` INT NULL ,
            `mkdate` INT NULL ,
            PRIMARY KEY (`id`)
        )");

        $db->exec("CREATE  TABLE `mooc_userprogress` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `block_id` INT NOT NULL ,
            `user_id` VARCHAR(32) NULL ,
            `json_data` MEDIUMTEXT NULL COMMENT 'JSON' ,
            `grade` VARCHAR(45) NULL ,
            `max_grade` VARCHAR(45) NULL ,
            PRIMARY KEY (`id`)
        )");

        SimpleORMap::expireTableScheme();
    }

    public function down () {
        DBManager::get()->exec("DROP TABLE mooc_blocks");
        DBManager::get()->exec("DROP TABLE mooc_userprogress");
        SimpleORMap::expireTableScheme();
    }
}
