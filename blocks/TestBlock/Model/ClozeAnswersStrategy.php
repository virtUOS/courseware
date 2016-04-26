<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * Answers strategy for cloze exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class ClozeAnswersStrategy extends AnswersStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getQuestion()
    {
        return $this->vipsExercise->getSolveTemplate()->render();
    }

    /**
     * {@inheritDoc}
     */
    public function getSolution(array $solution = null)
    {
        $request = array();
        foreach ($solution as $key => $value) {
            $request['answer_' . $key] = $value;
        }

        $xml = $this->vipsExercise->genSolution($request);

        return $this->vipsExercise->getCorrectionTemplate($xml)->render();
    }
}
