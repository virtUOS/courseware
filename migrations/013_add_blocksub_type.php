<?php

/**
 * Migration adding a sub_type column to the mooc_blocks table.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class AddBlockSubType extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return 'Adds a sub_type to blocks';
    }

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $db = DBManager::get();
        $db->exec('ALTER TABLE mooc_blocks ADD COLUMN sub_type VARCHAR(64) DEFAULT NULL AFTER type');
        $db->exec('UPDATE mooc_blocks SET sub_type = "selftest" WHERE type ="TestBlock"');
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $db = DBManager::get();
        $db->exec('ALTER TABLE mooc_blocks DROP COLUMN sub_type');
    }
}
