<?php
/**
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class AddFieldsTable extends Migration
{

    function description()
    {
        return 'Don\'t allow null values in the type column.';
    }

    function up()
    {
        $db = DBManager::get();
        $db->exec('CREATE  TABLE IF NOT EXISTS `mooc_fields` (
            `block_id` INT NOT NULL ,
            `user_id` VARCHAR(32) NOT NULL ,
            `name` VARCHAR(255) NOT NULL ,
            `json_data` MEDIUMTEXT NULL ,
            PRIMARY KEY (`block_id`, `user_id`, `name`)
        )');

        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        $db = DBManager::get();
        $db->exec('DROP TABLE mooc_fields');

        SimpleORMap::expireTableScheme();
    }
}
