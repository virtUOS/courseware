<?php
namespace Mooc\DB;

/**
 * TODO
 *
 * @author  <mlunzena@uos.de>
 */
class UserProgress extends \SimpleORMap
{

    public function __construct($id = null) {
        $this->db_table = 'mooc_userprogress';


        $this->belongs_to['block'] = array(
            'class_name'  => 'Mooc\\DB\\Block',
            'foreign_key' => 'block_id');

        $this->belongs_to['user'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'user_id');

        parent::__construct($id);
    }
}
