<?php
namespace Mooc\DB;

/**
 * TODO
 *
 * @author  <mlunzena@uos.de>
 *
 * @property int    $block_id
 * @property Block  $block
 * @property string $user_id
 * @property \User  $user
 * @property float  $grade
 * @property float  $max_grade
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

        if ($this->isNew()) {
            $this->grade = 0;
            $this->max_grade = 1;
        }
    }

    /**
     * {@inheritdata}
     */
    public function setData($data, $reset = false)
    {
        // we need to ensure that the max_grade field is set before the
        // grade field since it is evaluated in setGrade()
        krsort($data);

        return parent::setData($data, $reset);
    }

    // get progress as a percentage [0.0, 1.0]
    public function getPercentage()
    {
        return $this->max_grade > 0 ? ($this->grade / $this->max_grade) : 0;
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
     * @param float $grade a floating point number between 0.0 and and the
     *                     value of the max_grade field
     *
     * @throws \InvalidArgumentException if the grade is not in the allowed
     *                                   range
     */
    protected function setGrade($grade)
    {
        if ($this->max_grade === null || $this->max_grade == 0) {
            $this->max_grade = 1;
        }

        if (!is_numeric($grade) || $grade < 0 || $grade > $this->max_grade) {
            throw new \InvalidArgumentException('Grade must be within [0..'.$this->max_grade.'].');
        }
        $this->content['grade'] = $grade;
    }
}
