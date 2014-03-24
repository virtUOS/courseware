<?php

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class RenameTestBlock extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function description()
    {
        return 'update Vips block names from Test to TestBlock';
    }

    /**
     * {@inheritDoc}
     */
    public function up()
    {
        DBManager::get()->exec(
            'UPDATE mooc_blocks SET type = "TestBlock" WHERE type = "Test"'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function down()
    {
        DBManager::get()->exec(
            'UPDATE mooc_blocks SET type = "Test" WHERE type = "TestBlock"'
        );
    }
}
