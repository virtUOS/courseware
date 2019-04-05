<?php

class AddChdateToUserProgress extends Migration
{
    public function description()
    {
        return 'adds a coloum for change date to user_progress table';
    }

    public function up()
    {
        $db = DBManager::get();
        $db->exec("
            ALTER TABLE `mooc_userprogress` ADD `chdate` DATETIME NOT NULL ;
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {

    }
}
