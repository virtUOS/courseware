<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Adds a config field that allows to store the display name of the plugin
 *
 * @author <mlunzena@uos.de>
 */
class AddPluginDisplayName extends Migration
{
    public function description()
    {
        return 'Adds a config field that allows to store the display name of the plugin.';
    }

    public function up()
    {
        Config::get()->create(\Mooc\PLUGIN_DISPLAY_NAME_ID, array(
            'value'       => 'Mooc.IP',
            'is_default'  => 1,
            'type'        => 'string',
            'range'       => 'global',
            'section'     => 'global',
            'description' => 'Angezeigter Name des Plugins'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        Config::get()->delete(\Mooc\PLUGIN_DISPLAY_NAME_ID);
    }
}
