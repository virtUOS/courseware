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
 * 
 * @property \Course $course
 * @property \User  $user
 */

class MailLog extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'mooc_maillog';

        $config['belongs_to']['course'] = array(
            'class_name' => '\\Course',
            'foreign_key' => 'seminar_id'
        );

        $config['belongs_to']['user'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'user_id'
        );

        parent::configure($config);
    }

    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    public function hasCertificate($user_id, $cid)
    {
        $cert = self::findOneBySQL('seminar_id = ? AND user_id = ? and mail_type = ?', array($cid, $user_id, 'certificate'));
        return $cert !== null;
    }

}