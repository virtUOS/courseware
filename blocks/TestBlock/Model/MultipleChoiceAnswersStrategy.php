<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * Answers strategy for multiple choice exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class MultipleChoiceAnswersStrategy extends AnswersStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getAnswers()
    {
        return array_map('formatReady', $this->vipsExercise->answerArray);
    }

    /**
     * {@inheritDoc}
     */
    public function getName($index)
    {
        return 'answer_'.$index;
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

        return $solution[$index] == 1;
    }

    /**
     * {@inheritDoc}
     */
    public function isCorrect($index)
    {
        return $this->vipsExercise->correctArray[$index] == 1;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserAnswers(array $solution = null)
    {
        if ($solution === null) {
            return array();
        }

        $userAnswers = array();
        $answers = $this->getAnswers();

        foreach ($solution as $index => $selected) {
            if ($selected == 1) {
                $userAnswers[] = $answers[$index];
            }
        }

        return $userAnswers;
    }

    /**
     * {@inheritDoc}
     */
    public function getSolution(array $solution = null)
    {
        #var_dump($solution, $this->vipsExercise);

        $request = array();
        foreach ($solution as $key => $value) {
            $request['answer_' . $key] = $value;
        }

        $xml = $this->vipsExercise->genSolution($request);

        return $this->vipsExercise->getCorrectionTemplate($xml)->render();
    }
}
