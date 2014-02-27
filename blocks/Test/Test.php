<?php

namespace Mooc\Ui;

use Mooc\Container;
use Mooc\DB\Vips\Test As TestModel;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class Test extends Block
{
    /**
     * @var \Mooc\DB\Vips\Test
     */
    private $test;

    public function initialize()
    {
        $this->defineField('test_id', \Mooc\SCOPE_BLOCK, null);
        $this->test = new TestModel($this->test_id);
    }

    public function student_view()
    {
        $exercises = array();

        foreach ($this->test->exercises as $exercise) {
            require_once __DIR__.'/../../../VipsPlugin/exercises/'.$exercise->URI.'.php';
            $vipsExercise = new $exercise->URI($exercise->Aufgabe, $exercise->ID);

            $entry = array(
                'question' => $vipsExercise->question,
                'answers' => array(),
            );

            foreach ($vipsExercise->answerArray[0] as $answer) {
                $entry['answers'][] = array(
                    'text' => $answer,
                );
            }

            $exercises[] =  $entry;
        }

        print_r($exercises);

        return array(
            'exercises' => $exercises,
        );
    }
}
