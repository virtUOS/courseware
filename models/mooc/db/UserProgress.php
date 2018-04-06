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
 * @property float  $chdate
 */
class UserProgress extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'mooc_userprogress';

        $config['belongs_to']['block'] = array(
            'class_name'  => 'Mooc\\DB\\Block',
            'foreign_key' => 'block_id');

        $config['belongs_to']['user'] = array(
            'class_name'  => 'User',
            'foreign_key' => 'user_id');

        parent::configure($config);
    }

    public function __construct($id = null) {
        parent::__construct($id);

        $this->registerCallback('before_store', 'denyNobodyProgress');

        if ($this->isNew()) {
            $this->grade = 0;
            $this->max_grade = 1;
            $this->chdate = (new \DateTime())->format('Y-m-d H:i:s');
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

    protected function setGrade($grade)
    {
        $this->content['grade'] = $grade;
        $this->chdate = (new \DateTime())->format('Y-m-d H:i:s');
    }

    public function denyNobodyProgress()
    {
        return $this->content['user_id'] != 'nobody';
    }
}
