<?php
class ChangeMaxGradeDefault extends DBMigration {

    public function description () {
        return 'Changes userprogress\'s pk.';
    }

    public function up () {
        $db = DBManager::get();
        $db->exec('ALTER TABLE `mooc_userprogress` CHANGE `max_grade` `max_grade` DOUBLE NOT NULL DEFAULT  \'1.0\'');
        SimpleORMap::expireTableScheme();
    }

    public function down () {
        $db = DBManager::get();
        $db->exec('ALTER TABLE `mooc_userprogress` CHANGE `max_grade` `max_grade` DOUBLE NULL DEFAULT NULL');
        SimpleORMap::expireTableScheme();
    }
}
