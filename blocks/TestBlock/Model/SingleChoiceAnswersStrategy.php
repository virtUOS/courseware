<?php

namespace Mooc\TestBlock\Model;

/**
 * Answers strategy for single choice exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class SingleChoiceAnswersStrategy extends AbstractAnswersStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getAnswers()
    {
        return $this->vipsExercise->answerArray[0];
    }

    /**
     * {@inheritDoc}
     */
    public function getName($index)
    {
        return 'answer_0';
    }

    /**
     * {@inheritDoc}
     */
    public function isSelected($index, array $solution = null)
    {
        $parentDecision = parent::isSelected($index, $solution);

        if ($parentDecision !== null) {
            return $parentDecision;
        }

        return $solution[0] == $index;
    }

    /**
     * {@inheritDoc}
     */
    public function isCorrect($index)
    {
        return $this->vipsExercise->correctArray[0] == $index;
    }
}
