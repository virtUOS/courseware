<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class Solution extends \SimpleORMap
{
    public function __construct($id = null)
    {
        $this->db_table = 'vips_solution';

        parent::__construct($id);
    }

    /**
     * Finds a user's Solution of an Exercise that is part of a particular Test.
     *
     * @param Test          $test
     * @param Exercise      $exercise
     * @param \Seminar_User $user
     *
     * @return Solution
     */
    public static function findOneBy(Test $test, Exercise $exercise, \Seminar_User $user)
    {
        $solutions = static::findBySQL(
            'exercise_id = :exercise_id AND test_id = :test_id AND user_id = :user_id',
            array(
                ':exercise_id' => $exercise->id,
                ':test_id' => $test->id,
                ':user_id' => $user->cfg->getUserId(),
            )
        );

        if (count($solutions) > 0) {
            return $solutions[0];
        }

        return null;
    }
}
 