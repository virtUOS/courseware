<?php

/**
 * Create a Config for blockadder favorites.
 *
 * @author <rlucke@uos.de>
 */


class CreateFavoriteBlocksConfig extends Migration
{
    public function description()
    {
        return 'settings for blockadder favorites';
    }

    public function up()
    {
        $db = DBManager::get();

        $description = 'Speichert bevorzugte Courseware Blöcke';

        Config::get()->create('COURSEWARE_FAVORITE_BLOCKS', array(
            'type' => 'array', 'value' => 0, 'description' => $description, 'range' => 'user'
        ));

        $datafields = \DatafieldEntryModel::findBySql("datafield_id = '446e9485d92e1eef776a8ccf99849182'");
        foreach ($datafields as $field){
            $user_id = $field->range_id;
            $content = json_decode($field->content, true);
            UserConfig::get($user_id)->store('COURSEWARE_FAVORITE_BLOCKS', $content);
            $field->delete();
        }

        $db->exec("DELETE FROM `datafields` WHERE `datafield_id` = '446e9485d92e1eef776a8ccf99849182'");

        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        Config::get()->delete('COURSEWARE_FAVORITE_BLOCKS');

        SimpleORMap::expireTableScheme();
    }
}


