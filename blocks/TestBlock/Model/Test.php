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
}
