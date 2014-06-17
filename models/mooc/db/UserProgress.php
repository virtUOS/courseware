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

    /**
     * Grade must be a float in [0..1]
     *
     * As as is usual with SimpleORMap this method gets called on
     *
     * \code
     * $progress->grade = 0.5;
     * \endcode
     *
     * @param float $grade  a floating point number between 0.0 and 1.0
     */
    protected function setGrade($grade)
    {
        if (!is_numeric($grade) || $grade < 0 || $grade > 1) {
            throw new \InvalidArgumentException('Grade must be within [0..1].');
        }
        $this->content['grade'] = $grade;
    }

}
