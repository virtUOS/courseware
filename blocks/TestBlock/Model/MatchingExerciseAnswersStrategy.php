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
                'question' => $this->vipsExercise->defaultArray[$i],
                'answer' => $this->vipsExercise->answerArray[$i],
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
                'question' => $this->vipsExercise->defaultArray[$i],
                'answer' => $this->vipsExercise->answerArray[$index],
                'correct' => $correct,
            );
        }

        return $entries;
    }
}
