<?php

/**
 * Currently, the null values are allowed for the type column in the mooc_blocks
 * table. With this, the default value specified via the default_values property
 * in the AbstractBlock class is never applied.
 *
 * @author Christian Flothmann <cflothma@uos.de>
 */
class ModifyBlockTypeColumn extends Migration
{
    /**
     * {@inheritDoc}
     */
    function description()
    {
        return 'Don\'t allow null values in the type column.';
    }

    /**
     * {@inheritDoc}
     */
    function up()
    {
        $db = DBManager::get();
        $db->exec('ALTER TABLE `mooc_blocks` MODIFY `type` VARCHAR(64) NOT NULL');

        SimpleORMap::expireTableScheme();
    }

    /**
     * {@inheritDoc}
     */
    function down()
    {
        $db = DBManager::get();
        $db->exec('ALTER TABLE `mooc_blocks` MODIFY `type` VARCHAR(64) NULL');

        SimpleORMap::expireTableScheme();
    }
}
