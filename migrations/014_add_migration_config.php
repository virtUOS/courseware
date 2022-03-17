<?php

/**
 * Create a Config for migration config.
 *
 * @author <rlucke@uos.de>
 */


class AddMigrationConfig extends Migration
{
    public function description()
    {
        return 'add config for manual courseware content migration';
    }

    public function up()
    {
        $description = 'Lehrende dürfen Courseware-Inhalte selber in ihren Veranstaltungen migrieren.';

        Config::get()->create('COURSEWARE_MANUAL_MIGRATION', array(
            'type' => 'boolean', 'value' => 0, 'description' => $description, 'range' => 'global', 'section' => 'Courseware'
        ));

        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        Config::get()->delete('COURSEWARE_MANUAL_MIGRATION');

        SimpleORMap::expireTableScheme();
    }
}
