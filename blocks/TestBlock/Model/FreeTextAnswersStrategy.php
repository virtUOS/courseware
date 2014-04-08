<?php

namespace Mooc\TestBlock\Model;

/**
 * Answers strategy for free text exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class FreeTextAnswersStrategy extends AnswersStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getUserAnswers(array $solution = null)
    {
        if ($solution === null) {
            return array();
        }

        return $solution;
    }

    /**
     * {@inheritDoc}
     */
    public function isUserAnswerCorrect($userAnswer, $index)
    {
        foreach ($this->vipsExercise->answerArray as $index => $answer) {
            if ($answer != $userAnswer) {
                continue;
            }

            if ($this->vipsExercise->correctArray[$index] == 1) {
                return true;
            }
        }

        return false;
    }
}
