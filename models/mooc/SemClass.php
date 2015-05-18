<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class SemClass extends \SemClass
{
    /**
     * Returns the courses of this sem_class in Stud.IP
     *
     * @return \SimpleORMapCollection  a collection of all those courses
     */
    public function getCourses()
    {
        $class = $this->data['id'];
        $types = array_filter(array_map(function ($t) use ($class) {
            if ($t['class'] === $class) {
                return $t['id'];
            }

            return null;
        }, $GLOBALS['SEM_TYPE']));
        return \Course::findBySQL('status = ? AND visible = 1', array($types));
    }
}
