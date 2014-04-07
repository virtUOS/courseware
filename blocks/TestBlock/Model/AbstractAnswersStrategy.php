<?php

namespace Mooc\TestBlock\Model;

/**
 * Answers strategy for exercises.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
abstract class AbstractAnswersStrategy implements AnswersStrategyInterface
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
}
