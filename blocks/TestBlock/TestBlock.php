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

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        $options = json_decode($this->test->options);
        $properties = array(
            'test-id' => (int) $this->test->id,
            'type' => $this->test->type,
            'title' => $this->test->title,
            'halted' => $this->test->halted == 1 ? 'true' : 'false',
            'evaluation-mode' => (int) $options->evaluation_mode,
        );

        if ($options->shuffle_answers) {
            $properties['shuffle-answers'] = 'true';
        } else {
            $properties['shuffle-answers'] = 'false';
        }

        if ($options->printable) {
            $properties['printable'] = 'true';
        } else {
            $properties['printable'] = 'false';
        }

        if ($options->released) {
            $properties['released'] = 'true';
        } else {
            $properties['released'] = 'false';
        }

        return $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/test/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/test/test-1.0.xsd';
    }

    /**
     * Exports the block as a list of XML DOM node objects.
     *
     * @param \DOMDocument $document The document the nodes are created for
     * @param string       $alias    The namespace alias to be used to prefix
     *                               generated node names
     *
     * @return \DOMNode[] The generated nodes
     */
    public function exportContentsForXml(\DOMDocument $document, $alias)
    {
        if ($this->test->id == 0) {
            return array();
        }

        $descriptionNode = $document->createElement($alias.':description', $this->test->description);
        $exercisesNode = $document->createElement($alias.':exercises');

        foreach ($this->test->exercises as $exercise) {
            /** @var \Mooc\UI\TestBlock\Model\Exercise $exercise */

            $exerciseNode = $document->createElement($alias.':exercise');
            $idNode = $document->createAttribute('id');
            $idNode->value = $exercise->ID;
            $exerciseNode->appendChild($idNode);
            $nameNode = $document->createAttribute('name');
            $nameNode->value = utf8_encode($exercise->name);
            $exerciseNode->appendChild($nameNode);
            $typeNode = $document->createAttribute('type');
            $typeNode->value = $exercise->URI;
            $exerciseNode->appendChild($typeNode);

            $exerciseContent = new \DOMDocument();
            $exerciseContent->loadXML(utf8_encode($exercise->Aufgabe));
            $this->importNode($exerciseContent->documentElement, $exerciseNode, $alias);

            $exercisesNode->appendChild($exerciseNode);
        }

        return array($descriptionNode, $exercisesNode);
    }

    /**
     * Recursively import a node tree applying a namespace prefix to each
     * node name.
     *
     * @param \DOMNode $node   The node tree to import
     * @param \DOMNode $parent The parent node where the tree will be imported
     * @param string   $alias  The namespace prefix
     */
    private function importNode(\DOMNode $node, \DOMNode $parent, $alias)
    {
        if ($node instanceof \DOMText) {
            $textNode = new \DOMText($node->nodeValue);
            $parent->appendChild($textNode);

            return;
        }

        $newNode = $parent->ownerDocument->createElement($alias.':'.$node->nodeName);
        $parent->appendChild($newNode);

        foreach ($node->attributes as $attribute) {
            $newAttribute = $parent->ownerDocument->createAttribute($attribute->nodeName);
            $newAttribute->value = $attribute->value;
            $newNode->appendChild($newAttribute);
        }

        foreach ($node->childNodes as $child) {
            $this->importNode($child, $newNode, $alias);
        }
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
