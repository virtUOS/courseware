<?php

namespace Mooc\DB;


/**
 * @author Ron Lucke <lucke@elan-ev.de>
 *
 * @property int     $id
 * @property string  $seminar_id
 * @property int     $mkdate
 * @property \Course $course
 */
class MigrationStatus extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'mooc_migration_status';

        $config['belongs_to']['course'] = [
            'class_name' => \Course::class,
            'foreign_key' => 'seminar_id',
        ];

        parent::configure($config);
    }

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    public function resetMigrationStatus()
    {
        $data = self::findBySQL("seminar_id != ''");
        foreach($data as $status) {
            $status->delete();
        }
    }
}