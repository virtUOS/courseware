<?php

namespace Mooc\TestBlock\Model;

/**
 * Answers strategy for exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
interface AnswersStrategyInterface
{
    /**
     * Returns the possible answers.
     *
     * @return string[] The (human readable) answers
     */
    public function getAnswers();

    /**
     * Returns the internal representation of an answer.
     *
     * @param int $index The number of the answer
     *
     * @return string The answer name
     */
    public function getName($index);

    /**
     * Checks whether a user selected a particular answer.
     *
     * @param int   $index    The number of the answer
     * @param array $solution The user's solution (if available)
     *
     * @return boolean True, if the user selected the answer, false otherwise
     */
    public function isSelected($index, array $solution = null);

    /**
     * Checks whether a certain answer is correct.
     *
     * @param int $index The number of the answer
     *
     * @return boolean True, if the answer is correct, false otherwise
     */
    public function isCorrect($index);

    /**
     * Returns the template to be used to render the answers.
     *
     * @return string The template
     */
    public function getTemplate();
}
