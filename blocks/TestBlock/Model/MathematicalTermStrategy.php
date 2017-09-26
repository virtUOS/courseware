<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * Answers strategy for single choice exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class MathematicalTermStrategy extends AnswersStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getSolution(array $solution = null)
    {
        $xml = $this->vipsExercise->genSolution(array('answer_student' => $solution[0]));

        return $this->vipsExercise->getCorrectionTemplate($xml)->render();
    }
}
