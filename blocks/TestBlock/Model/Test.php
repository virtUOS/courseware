<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 *
 * @property string $type        The test type (one of "exam", "practice", "selftest")
 * @property string $course_id   The id of the course the test belongs to
 * @property int    $position    The position of the test in the list view
 * @property string $title       The test title
 * @property string $description The test description
 * @property string $user_id     The id of the user who created the test
 */
class Test extends \SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'vips_test';

        $this->has_and_belongs_to_many['exercises'] = array(
            'class_name' => 'Mooc\UI\TestBlock\Model\Exercise',
            'thru_table' => 'vips_exercise_ref',
            'thru_key' => 'test_id',
            'thru_assoc_key' => 'exercise_id',
            'assoc_foreign_key' => 'ID',
            'on_store' => true,
        );

        $this->has_many['refs'] = array(
            'class_name' => 'Mooc\UI\TestBlock\Model\Refs',
            'foreign_key' => 'id',
            'assoc_foreign_key' => 'test_id',
        );

        parent::__construct($id);


        foreach ($this->exercises as $exc) {
            $exc->setPoints($this->refs->filter(function ($ref) use ($exc) {
                return $exc->id == $ref->exercise_id;
            })->first()->points);
        }

    }

    public function isSelfTest()
    {
        return $this->type == 'selftest';
    }

    public function isExerciseSheet()
    {
        return $this->type == 'practise';
    }

    public function showCorrection()
    {
        return $this->isSelfTest();
    }

    /**
     * Returns all tests.
     *
     * @return Test[] The tests
     */
    public static function findAll()
    {
        return static::findBySQL('1 = 1 ORDER BY title');
    }

    /**
     * Returns all tests of a course which are of a given type (one of "exam",
     * "practice", "selftest").
     *
     * @param string $courseId The course id
     * @param string $type     The test type
     *
     * @return Test[] The tests
     *
     * @throws \InvalidArgumentException if $type is not one of "exam",
     *                                   "practice", "selftest"
     */
    public static function findAllByType($courseId, $type)
    {
        if (!in_array($type, array('exam', 'practice', 'selftest'))) {
            throw new \InvalidArgumentException(sprintf(
                'The test type must be one of "exam", "practice", "selftest" ("%s" given).',
                $type
            ));
        }

        return static::findBySQL('type = :type AND course_id = :course_id ORDER BY title', array(
            ':type' => $type,
            ':course_id' => $courseId,
        ));
    }
}
