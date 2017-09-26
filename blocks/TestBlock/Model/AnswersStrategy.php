<?php

namespace Mooc\UI\TestBlock\Model;

/**
 * Answers strategy for exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
abstract class AnswersStrategy implements AnswersStrategyInterface
{
    /**
     * @var \Exercise The Vips exercise
     */
    protected $vipsExercise;

    public function __construct(\Exercise $exercise)
    {
        $this->vipsExercise = $exercise;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return get_class($this->vipsExercise);
    }

    /**
     * {@inheritDoc}
     */
    public function getQuestion($solution_xml = null)
    {
        return $this->vipsExercise->getSolveTemplate($solution_xml)->render();
    }

    /**
     * {@inheritDoc}
     */
    public function getAnswers()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function getName($index)
    {
        null;
    }

    /**
     * {@inheritDoc}
     */
    public function isSelected($index, array $solution = null)
    {
        if ($solution === null) {
            return false;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function isCorrect($index)
    {
        false;
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplate()
    {
        $fullyQualifiedClassName = get_class($this);
        $className = substr($fullyQualifiedClassName, strrpos($fullyQualifiedClassName, '\\') + 1);

        return strtolower(preg_replace(
            array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'),
            array('\\1_\\2', '\\1_\\2'),
            substr($className, 0, strlen($className) - strlen('Strategy'))
        ));
    }

    /**
     * Creates and returns an answers strategy for the given Vips exercise.
     *
     * @param \exercise $vipsExercise The Vips exercise to manage
     *
     * @return AnswersStrategyInterface The answers strategy
     *
     * @throws \InvalidArgumentException if no answers strategy for the given
     *                                   Vips exercise does exist
     */
    public static function getStrategy(\exercise $vipsExercise)
    {
        $className = null;

        switch (get_class($vipsExercise)) {
            case 'mc_exercise':
                $className = 'MultipleChoiceAnswersStrategy';
                break;
            case 'sc_exercise':
                $className = 'SingleChoiceAnswersStrategy';
                break;
            case 'yn_exercise':
                $className = 'YesNoChoiceAnswersStrategy';
                break;
            case 'lt_exercise':
            case 'tb_exercise':
                $className = 'FreeTextAnswersStrategy';
                break;
            case 'cloze_exercise':
                $className = 'ClozeAnswersStrategy';
                break;
            case 'rh_exercise':
                $className = 'MatchingExerciseAnswersStrategy';
                break;
            case 'rnd_exercise':
                $className = 'RandomExerciseAnswersStrategy';
            case 'me_exercise':
                $className = 'MathematicalTermStrategy';
                break;
        }

        if ($className === null) {
            return null;
        }

        $fullyQualifiedClassName = '\Mooc\UI\TestBlock\Model\\'.$className;

        return new $fullyQualifiedClassName($vipsExercise);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserAnswers(array $solution = null)
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function isUserAnswerCorrect($answer, $index)
    {
        return false;
    }
}
