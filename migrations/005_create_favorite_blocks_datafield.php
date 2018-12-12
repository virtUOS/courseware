<?php

/**
 * Create a Datafield for blockadder favorites.
 *
 * @author <rlucke@uos.de>
 */


class CreateFavoriteBlocksDatafield extends Migration
{
    public function description()
    {
        return 'create a Datafield for blockadder favorites';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("
            INSERT INTO `datafields` (
                `datafield_id`, `name`, `object_type`, `object_class`, `edit_perms`, `view_perms`,
                `priority`, `mkdate`, `chdate`, `type`, `typeparam`, `is_required`, `is_userfilter`,
                `description`, `system`
            ) VALUES (
                '446e9485d92e1eef776a8ccf99849182', 'Courseware: favorite blocks', 'user', NULL, 'tutor', 'dozent',
                0, 1534291200, 1534291200, 'bool', '', 0, 0,
                'In diesem Datenfeld werden die favorisierten Blöcke eines Nutzers gespeichert', 0
            )");

        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->exec("DELETE FROM `datafields` WHERE `datafield_id` = '446e9485d92e1eef776a8ccf99849182'");

        SimpleORMap::expireTableScheme();
    }
}


