<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
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

        parent::__construct($id);
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
     * Returns all tests which are of a given type (one of "exam", "practice",
     * "selftest").
     *
     * @param string $type The test type
     *
     * @return Test[] The tests
     *
     * @throws \InvalidArgumentException if $type is not one of "exam",
     *                                   "practice", "selftest"
     */
    public static function findAllByType($type)
    {
        if (!in_array($type, array('exam', 'practice', 'selftest'))) {
            throw new \InvalidArgumentException(sprintf(
                'The test type must be one of "exam", "practice", "selftest" ("%s" given).',
                $type
            ));
        }

        return static::findBySQL('type = :type ORDER BY title', array(':type' => $type));
    }
}
