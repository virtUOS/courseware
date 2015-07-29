<?php

namespace Mooc\UI\TestBlock;

use Mooc\Container;
use Mooc\UI\Block;
use Mooc\UI\Section\Section;
use Mooc\UI\TestBlock\Model\Exercise;
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

    /**
     * @var array Cache of imported tests, used to avoid creating tests twice during imports
     */
    private static $importedTests = array();

    /**
     * @var array Cache of imported exercises, used to avoid creating exercises twice during imports
     */
    private static $importedExercises = array();

    public function __construct(Container $container, \SimpleORMap $model)
    {
        parent::__construct($container, $model);
    }

    public function initialize()
    {
        global $vipsPlugin, $vipsTemplateFactory;

        $this->defineField('test_id', \Mooc\SCOPE_BLOCK, null);

        $vipsPlugin = VipsBridge::getVipsPlugin();
        $vipsTemplateFactory = new \Flexi_TemplateFactory(VipsBridge::getVipsPath().'/templates/');

        $this->loadRelatedTest();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubTypes()
    {
        return array(
            // removed via https://github.com/virtUOS/courseware/issues/19
            // 'exam' => _('Klausur'),
            'selftest' => _('Selbsttest'),
            'practice' => _('Übungsblatt'),
        );
    }

    public function student_view()
    {
        $active = VipsBridge::vipsActivated($this);
        $typeOfThisTest = $this->test->type;
        $typeOfThisTestBlock = $this->_model->sub_type;
        $blockId = $this->_model->id;
        
        if ($typeOfThisTest == null) {return array('active' => $active, 'exercises' => false, 'typemismatch' => false);}
        if ($typeOfThisTest !== $typeOfThisTestBlock) {return array('active' => $active, 'exercises' => false, 'typemismatch' => true);}
        return $active ? array_merge(array('active' => $active, 'blockid' => $blockId), $this->buildExercises()) : compact('active');
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        if (!$active = VipsBridge::vipsActivated($this)) {
            return compact('active');
        }

        $storedTests = Test::findAllByType($this->_model->course->id, $this->_model->sub_type);
        $tests       = array();

        foreach ($storedTests as $test) {
            $tests[] = array(
                'id'              => $test->id,
                'name'            => $test->title,
                'created'         => isset($test->created) ? date('d.m.Y', strtotime($test->created)) : '',
                'exercises_count' => count($test->exercises),
                'current_test'    => $this->test_id === $test->id,
            );
        }

        return array(
            'active'           => $active,
            'manage_tests_url' => \PluginEngine::getURL(VipsBridge::getVipsPlugin(), array('action' => 'sheets'), 'show'),
            'tests'            => $tests,
        );
    }


    // ***** HANDLERS *****

    // preclude any calls to handlers
    public function handle($name, $data = array())
    {

        if (!VipsBridge::vipsActivated($this)) {
            throw new \RuntimeException('Vips is not activated.');
        }

        return parent::handle($name, $data);
    }



    public function modify_test_handler($testId)
    {
        $this->authorizeUpdate();

        // change the test id
        $this->test_id = $testId;

        // and reload the test data
        $this->test = new Test($this->test_id);

        return $this->buildExercises();
    }

    public function exercise_reset_handler($data)
    {
        $user = $this->container['current_user'];

        parse_str($data, $requestData);

        $testId = $requestData['test_id'];
        $exerciseId = $requestData['exercise_id'];

        \check_test_access($testId);

        $db = \DBManager::get();

        // do not allow to delete solutions if the test is no selftest
        $stmt = $db->prepare(
            'SELECT
              type
            FROM
              vips_test
            WHERE
              id = :test_id'
        );
        $stmt->bindValue(':test_id', $testId);
        $stmt->execute();

        if ($stmt->fetchColumn() != 'selftest') {
            return false;
        }

        // delete the solution
        $stmt = $db->prepare(
            'DELETE FROM
              vips_solution
            WHERE
              test_id = :test_id AND
              exercise_id = :exercise_id AND
              user_id = :user_id'
        );
        $stmt->bindValue(':test_id', $testId);
        $stmt->bindValue(':exercise_id', $exerciseId);
        $stmt->bindValue(':user_id', $user->id);
        $stmt->execute();

        // determine the number of solutions of the current user that do still
        // exist
        $stmt = $db->prepare(
            'SELECT
              COUNT(*)
            FROM
              vips_solution
            WHERE
              test_id = :test_id AND
              user_id = :user_id'
        );
        $stmt->bindValue(':test_id', $testId);
        $stmt->bindValue(':user_id', $user->id);
        $stmt->execute();

        // there are no solutions left, clean the solve start time
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare(
                'DELETE FROM
                  vips_aufgaben_zeit
                WHERE
                  test_id = :test_id AND
                  user_id = :user_id'
            );
            $stmt->bindValue(':test_id', $testId);
            $stmt->bindValue(':user_id', $user->id);
            $stmt->execute();
        }

        $this->calcGrades();

        return array();
    }

    public function exercise_submit_handler($data)
    {
        global $vipsPlugin, $vipsTemplateFactory;

        $user = $this->container['current_user'];

        parse_str($data, $requestParams);

        foreach ($requestParams as $key => $value) {
            // TODO: Why don't we use $data directly?
            $_POST[$key] = studip_utf8decode($value);
        }

        $vipsPlugin = VipsBridge::getVipsPlugin();
        $vipsTemplateFactory = new \Flexi_TemplateFactory(VipsBridge::getVipsPath().'/templates/');

        \submit_exercise('sheets');
        ob_clean();

        $this->calcGrades();

        return array();
     }

     public function calcGrades()
     {
         global $user;
         $progress = $this->getProgress();
         $progress->max_grade = count($this->test->exercises);
         $progress->grade = 0;

         foreach ($this->test->exercises as $exc) {
             $solution = $exc->getSolutionFor($this->test, $user);
             $correct = $solution ? ($exc->getPoints() == $solution->points) : false;
             if ($correct) {
                 $progress->grade++;
             }
         }

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

        if ($this->test->start != '0000-00-00 00:00:00') {
            $properties['start'] = date('Y-m-d\TH:i:s', strtotime($this->test->start));
        }

        if ($this->test->end != '0000-00-00 00:00:00') {
            $properties['end'] = date('Y-m-d\TH:i:s', strtotime($this->test->end));
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
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        /** @var \Seminar_User $user */
        global $user;

        $importedTestId = $properties['test-id'];

        // no test included in the import format
        if ($importedTestId <= 0) {
            $this->test_id = null;
            $this->save();

            return;
        }

        $courseId = $this->getModel()->course->id;

        // the test being imported has already been imported, reuse it
        if (isset(static::$importedTests[$courseId][$importedTestId])) {
            $this->test = new Test(static::$importedTests[$courseId][$importedTestId]);
            $this->test_id = $this->test->id;
            $this->save();

            return;
        }

        // create a new test and set all of its properties that are present
        // at this step
        $options = new \stdClass();
        $options->evaluation_mode = $properties['evaluation-mode'];

        if ($properties['shuffle-answers'] == 'true') {
            $options->shuffle_answers = true;
        } else {
            $options->shuffle_answers = false;
        }

        if ($properties['printable'] == 'true') {
            $options->printable = true;
        } else {
            $options->printable = false;
        }

        if ($properties['released'] == 'true') {
            $options->released = true;
        } else {
            $options->released = false;
        }

        $test = new Test();
        $test->type = $properties['type'];
        $test->course_id = $courseId;
        $test->position = VipsBridge::findNextVipsPosition($courseId);
        $test->title = $properties['title'];
        $test->description = '';
        $test->user_id = $user->cfg->getUserId();
        $test->options = json_encode($options);

        if ($properties['halted'] == 'true') {
            $test->halted = 1;
        } else {
            $test->halted = 0;
        }

        if (isset($properties['start'])) {
            $test->start = date('Y-m-d H:i:s', strtotime($properties['start']));
        } else {
            $test->start = date('Y-m-d H:i:s');
        }

        if (isset($properties['end'])) {
            $test->end = date('Y-m-d H:i:s', strtotime($properties['end']));
        } else {
            $test->end = date('Y-m-d H:i:s');
        }

        $test->store();
        $this->test = $test;
        $this->test_id = $test->id;
        static::$importedTests[$courseId][$importedTestId] = $test->id;
        $this->save();
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
     * Handles the import of the block's contents through XML.
     *
     * @param \DOMNode $node  The block's DOM node
     * @param string   $alias The namespace alias to be used to prefix
     *                        generated node names
     */
    public function importContentsFromXml(\DOMNode $node, $alias)
    {
        if ($this->test->id === null) {
            return;
        }

        $courseId = $this->getModel()->course->id;
        $xpath = new \DOMXPath($node->ownerDocument);

        $description = $xpath->query('./'.$alias.':description', $node);
        if ($description->length === 1) {
            $this->test->description = utf8_decode($description->item(0)->textContent);
            $this->test->store();
        }

        $exercises = $xpath->query('./'.$alias.':exercises/'.$alias.':exercise', $node);
        if ($exercises->length > 0) {
            foreach ($exercises as $exerciseData) {
                if ($exerciseData instanceof \DOMNode) {
                    $getAttribute = function ($name) use ($exerciseData) {
                        return $exerciseData->attributes->getNamedItem($name)->nodeValue;
                    };

                    if (!isset(static::$importedExercises[$courseId][$this->test->id][$getAttribute('id')])) {
                        $document = new \DOMDocument('1.0', 'utf-8');
                        $this->importNode($xpath->query('./*', $exerciseData)->item(0), $document, $alias, true, true);

                        $exercise = new Exercise();
                        $exercise->Name = utf8_decode($getAttribute('name'));
                        $exercise->Aufgabe = utf8_decode($document->saveXML());
                        $exercise->URI = $getAttribute('type');
                        $exercise->store();

                        static::$importedExercises[$courseId][$this->test->id][$getAttribute('id')] = $exercise;
                    }
                }
            }
        }

        // SimpleORMap can't handle many-to-many relationships with extra fields
        $db = \DBManager::get();
        $deleteStmt = $db->prepare('DELETE FROM vips_exercise_ref WHERE test_id = :test_id');
        $deleteStmt->bindParam(':test_id', $this->test->id);
        $deleteStmt->execute();
        $insertStmt = $db->prepare(
            'INSERT INTO
              vips_exercise_ref
            SET
              exercise_id = :exercise_id,
              test_id = :test_id,
              position = :position'
        );
        $position = 1;
        foreach (static::$importedExercises[$courseId][$this->test->id] as $exercise) {
            $insertStmt->bindParam(':exercise_id', $exercise->ID);
            $insertStmt->bindParam(':test_id', $this->test->id);
            $insertStmt->bindParam(':position', $position);
            $insertStmt->execute();
            $position++;
        }
    }

    /**
     * Recursively import a node tree either applying a namespace prefix to
     * each node name or stripping it off.
     *
     * @param \DOMNode $node                      The node tree to import
     * @param \DOMNode $parent                    The parent node where the
     *                                            tree will be imported
     * @param string   $alias                     The namespace prefix
     * @param bool     $stripPrefix               Whether or not to strip off
     *                                            the namespace prefix
     * @param bool     $ignoreWhiteSpaceTextNodes Whether or not to ignore text
     *                                            nodes that only consist of
     *                                            whitespaces
     */
    private function importNode(\DOMNode $node, \DOMNode $parent, $alias, $stripPrefix = false, $ignoreWhiteSpaceTextNodes = false)
    {
        if ($node instanceof \DOMText) {
            if ($ignoreWhiteSpaceTextNodes && trim($node->nodeValue) === '') {
                return;
            }

            $textNode = new \DOMText($node->nodeValue);
            $parent->appendChild($textNode);

            return;
        }

        if ($parent instanceof \DOMDocument) {
            $document = $parent;
        } else {
            $document = $parent->ownerDocument;
        }

        if ($stripPrefix && strpos($node->nodeName, $alias.':') === 0) {
            $nodeName = substr($node->nodeName, strlen($alias) + 1);
        } elseif ($stripPrefix) {
            $nodeName = $node->nodeName;
        } else {
            $nodeName = $alias.':'.$node->nodeName;
        }

        $newNode = $document->createElement($nodeName);
        $parent->appendChild($newNode);

        foreach ($node->attributes as $attribute) {
            $newAttribute = $document->createAttribute($attribute->nodeName);
            $newAttribute->value = $attribute->value;
            $newNode->appendChild($newAttribute);
        }

        foreach ($node->childNodes as $child) {
            $this->importNode($child, $newNode, $alias, $stripPrefix, $ignoreWhiteSpaceTextNodes);
        }
    }

    private function buildExercises()
    {
        /** @var \Seminar_User $user */
        global $user;

        $exercises = array();

        if ($this->test) {
            $numberofex =  count($this->test->exercises);
            $exindex = 1;
            foreach ($this->test->exercises as $exercise) {
                /** @var \Mooc\UI\TestBlock\Model\Exercise $exercise */

                // skip unsupported exercise types
                if ($exercise->getAnswersStrategy() === null) {
                    continue;
                }

                $answers = $exercise->getAnswers($this->test, $user);
                $userAnswers = $exercise->getUserAnswers($this->test, $user);
                
                if ($this->_model->sub_type == 'selftest') {
                    // TT: determine if a correct solution has been handed in
                    $solution = $exercise->getSolutionFor($this->test, $user);
                    $correct = $solution ? ($exercise->getPoints() == $solution->points) : false;
                    $tryagain = $solution && !$correct;
                }
                else {
                    $correct =  false; 
                    $tryagain = false;
                }

                $entry = array(
                    'exercise_type' => $exercise->getType(),
                    $exercise->getType() => 1,
                    'id' => $exercise->getId(),
                    'test_id' => $this->test->getId(),
                    'self_test' => $this->test->isSelfTest(),
                    'exercise_sheet' => $this->test->isExerciseSheet(),
                    'show_correction' => $this->test->showCorrection(),
                    'show_solution' => $exercise->showSolutionFor($this->test, $user) && $correct,
                    'title' => $exercise->getTitle(),
                    'question' => $exercise->getQuestion(),
                    'answers' => $answers,
                    'single-choice' => $exercise->isSingleChoice(),
                    'multiple-choice' => $exercise->isMultipleChoice(),
                    'solver_user_id' => $user->cfg->getUserId(),
                    'has_solution' => $exercise->hasSolutionFor($this->test, $user),
                    'solution' => $exercise->getAnswersStrategy()->getSolution($exercise->getVipsSolutionFor($this->test, $user)),
                    'solving_allowed' => $exercise->solvingAllowed($this->test, $user),
                    'number_of_answers' => count($answers),
                    'number_of_exercises' => $numberofex,
                    'exercise_index' => $exindex++,
                    $exercise->getAnswersStrategy()->getTemplate() => true,
                    'user_answers' => $userAnswers,
                    'correct' => $correct,
                    'tryagain' => $tryagain
                );
                $entry['skip_entry'] = !$entry['show_solution'] && !$entry['solving_allowed'];
                $exercises[] = $entry;
            }
        }

        return array(
            'title'       => $this->test->title,
            'description' => formatReady($this->test->description),
            'exercises'   => $exercises,
            'solved_completely' => $this->getProgress()->max_grade == $this->getProgress()->grade // all exercises solved?
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalInstanceAllowed($container, Section $section, $subType = null)
    {
        return VipsBridge::vipsActivated($section);
    }

    private function loadRelatedTest()
    {
        if (VipsBridge::vipsExists()) {
            $this->test = new Test($this->test_id);

            // do not allow tests that belong to other courses
            if ($this->test->course_id !== $this->_model->seminar_id) {
                $this->test = null;
            }

            if (!$this->_model->isNew()) {
                $progress = $this->getProgress();

                // initialize the user progress (if necessary)
                if ($progress->isNew()) {
                    $progress->grade = 0;
                    $progress->max_grade = count($this->test->exercises);
                    $progress->store();
                }

                // fix the max grade value if the number of exercises had changed
                if ($progress->max_grade != count($this->test->exercises)) {
                    $progress->max_grade = count($this->test->exercises);

                    if ($progress->grade > $progress->max_grade) {
                        $progress->grade = $progress->max_grade;
                    }

                    $progress->store();
                }
            }
        }
    }
}
