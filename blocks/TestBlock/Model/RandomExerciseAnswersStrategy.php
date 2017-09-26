<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * Random exercise answers strategy.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class RandomExerciseAnswersStrategy implements AnswersStrategyInterface
{
    /**
     * @var AnswersStrategyInterface The random exercise answer strategy
     */
    private $randomExerciseStrategy;

    public function __construct(\rnd_exercise $exercise)
    {
        /** @var \Seminar_User $user */
        global $user;

        $randomExerciseId = $exercise->chooseSubexercise($user->cfg->getUserId());
        $exercise = new Exercise($randomExerciseId);

        if ($exercise->getVipsExercise() !== null) {
            $this->randomExerciseStrategy = AnswersStrategy::getStrategy($exercise->getVipsExercise());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return $this->randomExerciseStrategy->getType();
    }

    /**
     * {@inheritDoc}
     */
    public function getQuestion()
    {
        return $this->randomExerciseStrategy->getQuestion();
    }

    /**
     * {@inheritDoc}
     */
    public function getAnswers()
    {
        return $this->randomExerciseStrategy->getAnswers();
    }

    /**
     * {@inheritDoc}
     */
    public function getName($index)
    {
        return $this->randomExerciseStrategy->getName($index);
    }

    /**
     * {@inheritDoc}
     */
    public function isSelected($index, array $solution = null)
    {
        return $this->randomExerciseStrategy->isSelected($index, $solution);
    }

    /**
     * {@inheritDoc}
     */
    public function isCorrect($index)
    {
        return $this->randomExerciseStrategy->isCorrect($index);
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplate()
    {
        return $this->randomExerciseStrategy->getTemplate();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserAnswers(array $solution = null)
    {
        return $this->randomExerciseStrategy->getUserAnswers($solution);
    }

    /**
     * {@inheritDoc}
     */
    public function isUserAnswerCorrect($answer, $index)
    {
        return $this->randomExerciseStrategy->isUserAnswerCorrect($answer, $index);
    }

    /**
     * {@inheritDoc}
     */
    public function getSolution(array $solution = null)
    {
        return $this->randomExerciseStrategy->getSolution($solution);
    }
}
