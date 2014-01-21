<?php
class AddMoocipTables extends DBMigration {

    public function description () {
        return 'create tables for the mooc-plugin';
    }

    public function up () {
        $db = DBManager::get();
        $db->exec("CREATE  TABLE `mooc_blocks` (
            `id` INT NOT NULL ,
            `type` VARCHAR(64) NULL ,
            `parent_id` VARCHAR(32) NULL ,
            `seminar_id` VARCHAR(32) NULL ,
            `title` VARCHAR(255) NULL ,
            `position` INT NULL DEFAULT 0 ,
            `data` MEDIUMTEXT NULL COMMENT 'JSON' ,
            `chdate` INT NULL ,
            `mkdate` INT NULL ,
            PRIMARY KEY (`id`)
        )");
        
        $db->exec("CREATE  TABLE `mooc_userprogress` (
            `id` INT NOT NULL ,
            `block_id` VARCHAR(32) NULL ,
            `user_id` VARCHAR(32) NULL ,
            `data` MEDIUMTEXT NULL COMMENT 'JSON' ,
            `grade` VARCHAR(45) NULL ,
            `max_grade` VARCHAR(45) NULL ,
            PRIMARY KEY (`id`)
        )");
    }
    
    public function down () {
        DBManager::get()->exec("DROP TABLE mooc_blocks");
        DBManager::get()->exec("DROP TABLE mooc_userprogress");
    }
}
