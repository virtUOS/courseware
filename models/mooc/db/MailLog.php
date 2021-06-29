<?php

namespace Mooc\DB;


/**
 * @author Ron Lucke <lucke@elan-ev.de>
 *
 * @property int     $id
 * @property string  $seminar_id
 * @property string  $user_id
 * @property string  $mail_type
 * @property int     $chdate
 * @property int     $mkdate
 * @property \Course $course
 * @property \User   $user
 */
class MailLog extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'mooc_maillog';

        $config['belongs_to']['course'] = [
            'class_name' => \Course::class,
            'foreign_key' => 'seminar_id',
        ];

        $config['belongs_to']['user'] = [
            'class_name' => \User::class,
            'foreign_key' => 'user_id',
        ];

        parent::configure($config);
    }

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    public static function getCertificate($user_id, $cid)
    {
        return self::findOneBySQL('seminar_id = ? AND user_id = ? and mail_type = ?', [$cid, $user_id, 'certificate']);
    }

    public static function hasCertificate($user_id, $cid)
    {
        return null !== self::getCertificate($user_id, $cid);
    }
}
