<?php

namespace Mooc\Ui;

use Mooc\DB\Vips\Test;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class TestBlock extends Block
{
    /**
     * @var \Mooc\DB\Vips\Test
     */
    private $test;

    public function initialize()
    {
        $this->defineField('test_id', \Mooc\SCOPE_BLOCK, null);
        if (\PluginEngine::getPlugin('VipsPlugin')) {
            $this->test = new Test($this->test_id);
        }
    }

    public function student_view()
    {
        $exercises = array();

        if ($this->test) {
            foreach ($this->test->exercises as $exercise) {
                require_once __DIR__.'/../../../VipsPlugin/exercises/'.$exercise->URI.'.php';
                $vipsExercise = new $exercise->URI($exercise->Aufgabe, $exercise->ID);

                $entry = array(
                    'question' => $vipsExercise->question,
                    'answers' => array(),
                );

                if (is_array($vipsExercise->answerArray[0])) {
                    $answers = $vipsExercise->answerArray[0];
                } else {
                    $answers = $vipsExercise->answerArray;
                }

                foreach ($answers as $answer) {
                    $entry['answers'][] = array(
                        'text' => $answer,
                    );
                }

                $exercises[] =  $entry;
            }
        }

        return array(
            'exercises' => $exercises,
        );
    }
}
