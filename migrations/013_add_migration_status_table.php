<?php

/**
 * Setup table mooc_migration_status for migration from plugin to core.
 *
 * @author    Ron Lucke <lucke@elan-ev.de>
 */


class AddMigrationStatusTable extends Migration
{
    public function description()
    {
        return 'Setup table mooc_migration_status';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `mooc_migration_status` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `seminar_id` varchar(32) NOT NULL,
          `mkdate` int(11) NOT NULL,
          PRIMARY KEY (`id`)
        )");

        SimpleORMap::expireTableScheme();

    }

    public function down()
    {
        // To avoid data loss, nothing is deleted by default
        // remove the following "return;"-statement to clean tables on uninstall
        return;
        DBManager::get()->exec("DROP TABLE mooc_migration_status");

        SimpleORMap::expireTableScheme();

        
    }
}

