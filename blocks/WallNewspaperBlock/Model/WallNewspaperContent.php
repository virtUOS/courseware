<?php
namespace Mooc\UI\WallNewspaperBlock\Model;

class WallNewspaperContent extends \SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'cw_wallnewspaper_content';

        $config['belongs_to']['mooc'] = array(
            'class_name' => 'Mooc\\DB\\Block',
            'foreign_key' => 'block_id'
        );

        parent::configure($config);
    }
}
