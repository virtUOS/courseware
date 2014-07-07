<?php

namespace Mooc\UI\TestBlock;

use Mooc\Container;
use Mooc\UI\Block;
use Mooc\UI\Section\Section;
use Mooc\UI\TestBlock\Model\Test;
use Mooc\UI\TestBlock\Vips\Bridge as VipsBridge;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class TestBlock extends Block
{
    const NAME = 'Quiz';

    /**
     * @var \Mooc\UI\TestBlock\Model\Test
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
        $storedTests = Test::findAll();
        $tests = array();

        foreach ($storedTests as $test) {
            $tests[] = array(
                'id' => $test->id,
                'name' => $test->title,
                'exercises_count' => count($test->exercises),
                'current_test' => $this->test_id === $test->id,
            );
        }

        return array(
            'manage_tests_url' => \PluginEngine::getURL(VipsBridge::getVipsPlugin(), array('action' => 'sheets'), 'show'),
            'tests' => $tests,
        );
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
                /** @var \Mooc\UI\TestBlock\Model\Exercise $exercise */

                // skip unsupported exercise types
                if ($exercise->getAnswersStrategy() === null) {
                    continue;
                }

                $answers = $exercise->getAnswers($this->test, $user);
                $userAnswers = $exercise->getUserAnswers($this->test, $user);
                $exercises[] = array(
                    'exercise_type' => $exercise->getType(),
                    $exercise->getType() => 1,
                    'id' => $exercise->getId(),
                    'test_id' => $this->test->getId(),
                    'self_test' => $this->test->isSelfTest(),
                    'exercise_sheet' => $this->test->isExerciseSheet(),
                    'show_correction' => $this->test->showCorrection(),
                    'question' => $exercise->getQuestion(),
                    'answers' => $answers,
                    'single-choice' => $exercise->isSingleChoice(),
                    'multiple-choice' => $exercise->isMultipleChoice(),
                    'solver_user_id' => $user->cfg->getUserId(),
                    'has_solution' => $exercise->hasSolutionFor($this->test, $user),
                    'solution' => $exercise->getAnswersStrategy()->getSolution($exercise->getVipsSolutionFor($this->test, $user)),
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

    /**
     * {@inheritdoc}
     */
    public static function additionalInstanceAllowed(Section $section)
    {
        return VipsBridge::vipsExists();
    }
}
