<?php
/**
 * Created by PhpStorm.
 * User: Tobias
 * Date: 02.06.2015
 * Time: 11:11
 */

namespace Mooc\UI\TestBlock\Model;


class Refs extends \SimpleORMap {


    protected static function configure($config = array())
    {
        $config['db_table'] = 'vips_exercise_ref';

        $config['belongs_to']['test'] = array(
            'class_name' => 'Mooc\UI\TestBlock\Model\Test',
            'foreign_key' => 'test_id',
            'assoc_foreign_key' => 'id'
        );
        $config['belongs_to']['exercise'] = array(
            'class_name' => 'Mooc\UI\TestBlock\Model\Exercise',
            'foreign_key' => 'exercise_id',
            'assoc_foreign_key' => 'ID'
        );

        $config['pk'] = array(
            'test_id', 'exercise_id'
        );

        parent::configure($config);

    }


    // HACK: vips_exercise_ref hat keinen PK, tu so als ob
    // ALTER TABLE `vips_exercise_ref` ADD PRIMARY KEY ( `exercise_id` , `test_id` ) ;
    protected function getTableScheme()
    {
        $scheme = parent::getTableScheme();
        if (!is_array($this->pk)) {
            self::$schemes['vips_exercise_ref']['pk'] = $this->pk = array('exercise_id', 'test_id');
        }
        return $scheme;
    }
}
