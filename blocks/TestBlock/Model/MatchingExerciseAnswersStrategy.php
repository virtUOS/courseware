<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * Answers strategy for matching exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class MatchingExerciseAnswersStrategy extends AnswersStrategy
{
    /**
     * {@inheritDoc}
     */
    public function getAnswers()
    {
        $entries = array();

        for ($i = 0; $i < count($this->vipsExercise->answerArray); $i++) {
            $entries[] = array(
                'question' => formatReady($this->vipsExercise->defaultArray[$i]),
                'answer'   => formatReady($this->vipsExercise->answerArray[$i]),
            );
        }

        return $entries;
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplate()
    {
        return 'matching_exercise_answers';
    }

    /**
     * {@inheritDoc}
     */
    public function getUserAnswers(array $solution = null)
    {
        if ($solution === null) {
            return array();
        }

        $entries = array();
        $indexToAnswer = array();

        foreach ($solution as $answer => $index) {
            $indexToAnswer[$index] = $answer;
        }

        for ($i = 0; $i < count($this->vipsExercise->answerArray); $i++) {
            $index = $i;
            $correct = false;

            if (isset($indexToAnswer[$i])) {
                $index = $indexToAnswer[$i];
                $correct = $this->vipsExercise->correctArray[$i] == $index;
            }

            $entries[] = array(
                'question'       => formatReady($this->vipsExercise->defaultArray[$i]),
                'answer'         => formatReady($this->vipsExercise->answerArray[$index]),
                'correct_answer' => formatReady($this->vipsExercise->answerArray[$this->vipsExercise->correctArray[$i]]),
                'correct'        => $correct,
            );
        }

        return $entries;
    }

    /**
     * {@inheritDoc}
     */
    public function getSolution(array $solution = null)
    {
        $xml = $this->vipsExercise->genSolution(array('student_answer' => $solution));

        return $this->vipsExercise->getCorrectionTemplate($xml)->render();
    }
}
