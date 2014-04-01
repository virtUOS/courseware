<?php

namespace Mooc\Ui;

use Mooc\Container;
use Mooc\TestBlock\Model\Solution;
use Mooc\TestBlock\Model\Test;
use Mooc\TestBlock\Vips\Bridge as VipsBridge;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class TestBlock extends Block
{
    /**
     * @var \Mooc\TestBlock\Model\Test
     */
    private $test;

    public function __construct(Container $container, \SimpleORMap $model)
    {
        parent::__construct($container, $model);
    }

    public function initialize()
    {
        $this->defineField('test_id', \Mooc\SCOPE_BLOCK, null);

        if (\PluginEngine::getPlugin('VipsPlugin')) {
            $this->test = new Test($this->test_id);
        }
    }

    public function student_view()
    {
        return $this->buildExercises();
    }

    public function author_view()
    {
        return $this->toJSON();
    }

    public function modify_test_handler($testId)
    {
        // change the test id
        $this->test_id = $testId;

        // and reload the test data
        $this->test = new Test($this->test_id);

        return $this->buildExercises();
    }

    private function buildExercises()
    {
        /** @var \Seminar_User $user */
        global $user;

        $exercises = array();

        $vipsPlugin = VipsBridge::getVipsPlugin();

        if ($this->test) {
            foreach ($this->test->exercises as $exercise) {
                require_once VipsBridge::getVipsPath().'/exercises/'.$exercise->URI.'.php';
                $vipsExercise = new $exercise->URI($exercise->Aufgabe, $exercise->ID);

                $solution = Solution::findOneBy($this->test, $exercise, $user);
                $vipsSolution = $vipsExercise->getTagsFromXML($solution->solution, 'answer');

                $entry = array(
                    'id' => $vipsExercise->id,
                    'test_id' => $this->test->id,
                    'question' => $vipsExercise->question,
                    'answers' => array(),
                    'single-choice' => $vipsExercise instanceof \sc_exercise,
                    'multiple-choice' => $vipsExercise instanceof \mc_exercise,
                    'solver_user_id' => $user->cfg->getUserId(),
                    'has_solution' => $solution === null ? false : true,
                );

                if (is_array($vipsExercise->answerArray[0])) {
                    $answers = $vipsExercise->answerArray[0];
                } else {
                    $answers = $vipsExercise->answerArray;
                }

                foreach ($answers as $index => $answer) {
                    $answerEntry = array(
                        'text' => $answer,
                        'index' => $index,
                        'checked' => false,
                        'checked_image' => $vipsPlugin->getPluginURL().'/images/choice_checked.png',
                        'unchecked_image' => $vipsPlugin->getPluginURL().'/images/choice_unchecked.png',
                        'correct_answer' => $vipsExercise->correctArray[$index] == 1,
                        'correct_image' => \Assets::image_path('icons/16/green/accept'),
                        'incorrect_image' => \Assets::image_path('icons/16/red/decline'),
                    );

                    if ($solution !== null && $vipsSolution[$index] == 1) {
                        $answerEntry['checked'] = true;
                    }
                    $entry['answers'][] = $answerEntry;
                }

                $entry['number_of_answers'] = count($answers);

                $exercises[] =  $entry;
            }
        }

        return array(
            'title' => $this->test->title,
            'exercises' => $exercises,
        );
    }
}
