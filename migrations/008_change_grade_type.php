<?php
class ChangeGradeType extends DBMigration {

    public function description () {
        return 'Changes grade\'s type to double.';
    }

    public function up () {
        $db = DBManager::get();
        $db->exec('ALTER TABLE  `mooc_userprogress` CHANGE  `grade`  `grade` DOUBLE NULL DEFAULT NULL');
        $db->exec('ALTER TABLE  `mooc_userprogress` CHANGE  `max_grade`  `max_grade` DOUBLE NULL DEFAULT NULL');
        SimpleORMap::expireTableScheme();
    }

    public function down () {
        $db = DBManager::get();
        $db->exec('ALTER TABLE  `mooc_userprogress` CHANGE  `grade`  `grade` VARCHAR(45) NULL');
        $db->exec('ALTER TABLE  `mooc_userprogress` CHANGE  `max_grade`  `max_grade` VARCHAR(45) NULL');
        SimpleORMap::expireTableScheme();
    }
}
