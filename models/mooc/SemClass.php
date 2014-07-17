<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class SemClass extends \SemClass
{
    public static function getMoocSemClass()
    {
        $id = self::getMoocSemClassID();
        return new self(intval($id));
    }

    /**
     * Returns the courses of this sem_class in Stud.IP
     *
     * @return SimpleORMapCollection  a collection of all those courses
     */
    public function getCourses()
    {
        $class = $this->data['id'];
        $types = array_filter(array_map(function ($t) use ($class) {
            if ($t['class'] === $class) {
                return $t['id'];
            }
        }, $GLOBALS['SEM_TYPE']));
        return \Course::findBySQL('status = ? AND visible = 1', array($types));
    }

    private static function getMoocSemClassID()
    {
        return \Config::get()->getValue(\Mooc\SEM_CLASS_CONFIG_ID);
    }
}
