<?php

namespace Mooc\UI\TestBlock\Model;

use Mooc\UI\TestBlock\Vips\Bridge as VipsBridge;

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
        #TODO: #FIXME siehe #379
        if ($this->vipsExercise->getType()) {
            return $userAnswer === $this->vipsExercise->answerArray[0];
        }

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


    /**
     * {@inheritDoc}
     */
    public function getSolution(array $solution = null)
    {
        #TODO: #FIXME get xml in a more convenient way!
        $xml = $this->vipsExercise->genSolution(array('answer_0' => $solution[0]));

        return $this->vipsExercise->getCorrectionTemplate($xml)->render();
    }
}
