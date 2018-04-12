<?php

class AddHiddenToMoocPosts extends Migration
{
    public function description()
    {
        return 'adds a coloum for hidden to mooc_posts table';
    }

    public function up()
    {
        $db = DBManager::get();
        $db->exec("
            ALTER TABLE `mooc_posts` ADD `hidden` TINYINT(1) NOT NULL DEFAULT '0' AFTER `content`;
        ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
    }
}
