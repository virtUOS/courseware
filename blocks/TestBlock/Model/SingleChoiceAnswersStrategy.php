<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * Answers strategy for single choice exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class SingleChoiceAnswersStrategy extends AnswersStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getAnswers()
    {
        return array_map('formatReady', $this->vipsExercise->answerArray[0]);
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

    /**
     * {@inheritDoc}
     */
    public function getUserAnswers(array $solution = null)
    {
        if ($solution === null) {
            return array();
        }

        $answers = $this->getAnswers();

        return array($answers[$solution[0]]);
    }

    /**
     * {@inheritDoc}
     */
    public function getSolution(array $solution = null)
    {
        $xml = $this->vipsExercise->genSolution(array('answer_0' => $solution[0]));

        return $this->vipsExercise->getCorrectionTemplate($xml)->render();
    }
}
