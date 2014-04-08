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

        if (VipsBridge::vipsExists()) {
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

    public function exercise_submit_handler($data)
    {
        global $vipsPlugin, $vipsTemplateFactory;

        parse_str($data, $requestParams);

        foreach ($requestParams as $key => $value) {
            $_POST[$key] = $value;
        }

        $vipsPlugin = VipsBridge::getVipsPlugin();
        $vipsTemplateFactory = new \Flexi_TemplateFactory(VipsBridge::getVipsPath().'/templates/');

        \submit_exercise('sheets');
        ob_clean();

        return array();
    }

    private function buildExercises()
    {
        /** @var \Seminar_User $user */
        global $user;

        $exercises = array();

        if ($this->test) {
            foreach ($this->test->exercises as $exercise) {
                /** @var \Mooc\TestBlock\Model\Exercise $exercise */

                $answers = $exercise->getAnswers($this->test, $user);
                $userAnswers = $exercise->getUserAnswers($this->test, $user);
                $exercises[] = array(
                    'exercise_type' => $exercise->getType(),
                    'id' => $exercise->getId(),
                    'test_id' => $this->test->getId(),
                    'question' => $exercise->getQuestion(),
                    'answers' => $answers,
                    'single-choice' => $exercise->isSingleChoice(),
                    'multiple-choice' => $exercise->isMultipleChoice(),
                    'solver_user_id' => $user->cfg->getUserId(),
                    'has_solution' => $exercise->hasSolutionFor($this->test, $user),
                    'number_of_answers' => count($answers),
                    $exercise->getAnswersStrategy()->getTemplate() => true,
                    'user_answers' => $userAnswers,
                );
            }
        }

        return array(
            'title' => $this->test->title,
            'exercises' => $exercises,
        );
    }
}
