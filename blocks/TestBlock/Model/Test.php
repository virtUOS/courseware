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
     * Filter the tests by a given term.
     *
     * @param string $term The term used to filter
     *
     * @return Test[] The tests that match a search term
     */
    public static function findByTerm($term)
    {
        return static::findBySQL('title LIKE :term', array(':term' => '%'.$term.'%'));
    }
}
