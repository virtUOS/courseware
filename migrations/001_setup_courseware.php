<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Setup tables for the mooc.ip courseware-plugin and adds editable display name of the plugin.
 *
 * @author <andre@gundk.it>
 */


class SetupCourseware extends DBMigration {

    public function description () {
        return 'Setup tables for the mooc.ip courseware-plugin and adds editable display name of the plugin.';
    }

    public function up () {
      
        $db = DBManager::get();
        
        $db->exec("CREATE TABLE IF NOT EXISTS `mooc_blocks` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `type` varchar(64) NOT NULL,
          `sub_type` varchar(64) DEFAULT NULL,
          `parent_id` int(11) DEFAULT NULL,
          `seminar_id` varchar(32) DEFAULT NULL,
          `title` varchar(255) DEFAULT NULL,
          `position` int(11) DEFAULT '0',
          `publication_date` int(11) DEFAULT NULL,
          `chdate` int(11) DEFAULT NULL,
          `mkdate` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`)
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS `mooc_fields` (
          `block_id` int(11) NOT NULL,
          `user_id` varchar(32) NOT NULL,
          `name` varchar(255) NOT NULL,
          `json_data` mediumtext,
          PRIMARY KEY (`block_id`,`user_id`,`name`)
        )");

        $db->exec("CREATE TABLE IF NOT EXISTS `mooc_userprogress` (
          `block_id` int(11) NOT NULL,
          `user_id` varchar(32) NOT NULL DEFAULT '',
          `grade` double DEFAULT NULL,
          `max_grade` double NOT NULL DEFAULT '1',
          PRIMARY KEY (`block_id`,`user_id`)
        )");

        Config::get()->create(\Mooc\PLUGIN_DISPLAY_NAME_ID, array(
            'value'       => 'Mooc.IP',
            'is_default'  => 1,
            'type'        => 'string',
            'range'       => 'global',
            'section'     => 'global',
            'description' => 'Angezeigter Name des Plugins'
        ));

        SimpleORMap::expireTableScheme();
    }

    public function down () {
      
        DBManager::get()->exec("DROP TABLE mooc_blocks");
        DBManager::get()->exec("DROP TABLE mooc_userprogress");
        DBManager::get()->exec("DROP TABLE mooc_fields");
        
        Config::get()->delete(\Mooc\PLUGIN_DISPLAY_NAME_ID);
        
        SimpleORMap::expireTableScheme();
    }
}
