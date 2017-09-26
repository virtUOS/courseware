<?
namespace Mooc\UI\TestBlock;

use Mooc\UI\Block;

/**
 * @author Ron Lucke <rlucke@uos.de>
 */

class TestBlock extends Block 
{
    const NAME = 'Quiz';

    function initialize()
    {
        $this->defineField('test_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('tries',   \Mooc\SCOPE_USER, array()); // Field to count the tries
    }

    public static function getSubTypes()
    {
        return array(
            'selftest' => _cw('Selbsttest'),
            'practice' => _cw('Übungsblatt'),
        );
    }

    function student_view()
    {   
        if ($this->container['current_user']->isNobody()) {
            return array(
                'is_nobody' => true
            );
        }
        if (!$installed = $this->vipsInstalled()) {
            return compact('installed');
        }
        if (!$active = $this->vipsActivated()) {
            return array('active' => $active, 'installed'=> $installed);
        }
        if (!$version = $this->vipsVersion()) {
            return array('active' => $active, 'version'=> $version, 'installed'=> $installed);
        }
        $this->calcGrades();
        $subtype =  $this->_model->sub_type;
        $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
        $type_mismatch = !($assignment->type ==  $subtype);
        if ($assignment->type == null) {
            return array(
                'exercises'     => false,
                'typemismatch'  => false,
                'active'        => $active, 
                'version'       => $version,
                'installed'     => $installed
            );
        }
        if ($type_mismatch) {
            return array(
                'exercises'     => false,
                'typemismatch'  => true,
                'active'        => $active, 
                'version'       => $version,
                'installed'     => $installed
            );
        }

        return array_merge($this->getAttrArray(), array('active' => $active, 'version' => $version, 'installed' => $installed), $this->buildExercises());
    }

    function author_view()
    {
        $this->authorizeUpdate();
        if (!$installed = $this->vipsInstalled()) {
            return compact('installed');
        }
        if (!$active = $this->vipsActivated()) {
            return array('active' => $active, 'installed'=> $installed);
        }
        if (!$version = $this->vipsVersion()) {
            return array('active' => $active, 'version'=> $version, 'installed'=> $installed);
        }
        $subtype =  $this->_model->sub_type;
        $stored_assignments = \VipsAssignment::findBySQL( 'course_id = ? and type = ?', array($this->_model->course->id, $subtype));
        $tests       = array();
        foreach ($stored_assignments as $assignment) {
            $test_id = $assignment->test_id;
            $test = \VipsTest::findOneBySQL('id = ?', array($test_id));
            $tests[] = array(
                'id'              => $test->id,
                'name'            => $test->title,
                'created'         => isset($test->created) ? date('d.m.Y', strtotime($test->created)) : '',
                'exercises_count' => count($test->exercises),
                'current_test'    => $this->test_id === $test->id
            );
            
        }
        return array_merge($this->getAttrArray(), array( 
            'has_tests'         => !empty($tests),
            'type'              => $this->getSubTypes()[$subtype],
            'tests'             => $tests, 
            'active'            => $active, 
            'version'           => $version,
            'installed'         => $installed,
            'manage_tests_url'  => \PluginEngine::getURL('VipsPlugin', array('action' => 'sheets'), 'show')
            ));
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset ($data['test_id'])) {
            $this->test_id = (string) $data['test_id'];
        } 
        return;
    }

    public function exercise_submit_handler($data)
    {
        parse_str($data, $requestParams);
        $requestParams = studip_utf8decode($requestParams);
        $test_id = $requestParams['assignment_id'];
        $exercise_id = $requestParams['exercise_id'];

        check_exercise_access($exercise_id, $test_id);

        $test = \VipsTest::findOneBySQL('id = ?', array($test_id));
        $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
        $exercise = \Exercise::find($exercise_id);

        // if it is a self test, count the tries
        if($assignment->type == "selftest") {
            if(!$this->tries) {
                $local_tries = array();
            } else {
                $local_tries = $this->tries;
            }
            if(!$local_tries[$exercise_id]) {
                $local_tries[$exercise_id] = 0;
            }
            $local_tries[$exercise_id] ++;
            $this->tries = $local_tries;
        }

        $start = $assignment->start;
        $end = $assignment->end;
        $now = date('Y-m-d H:i:s');

        // not yet started or already ended
        if ($start > $now || $now > $end) {
            throw new \Exception(_cw('Das Aufgabenblatt kann zur Zeit nicht bearbeitet werden.'));
        }

        $solution = $exercise->getSolutionFromRequest($requestParams);
        $assignment->storeSolution($solution);

        $progress = $this->calcGrades();
        return array(
            'grade' => $progress->max_grade > 0 ? $progress->grade / $progress->max_grade : 0
        );
    }

    public function exercise_reset_handler($data)
    {
        $user = $this->container['current_user'];

        parse_str($data, $requestData);
        $test_id     = $requestData['test_id'];
        $exercise_id = $requestData['exercise_id'];

        check_test_access($test_id);

        $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($test_id));

        $now = time();
        $start = strtotime($assignment->start);
        $end = strtotime($assignment->end);

        // not yet started or already ended
        if ($start > $now || $now > $end) {
            throw new \Exception(_cw('Das Aufgabenblatt kann zur Zeit nicht bearbeitet werden.'));
        }
        // resetting tries
        if(!$this->tries) {
            $local_tries = array();
        } else {
            $local_tries = $this->tries;
        }
        if ($local_tries) {
            $local_tries[$exercise_id] = 0;
            $this->tries = $local_tries;
        }
        $assignment->deleteSolution($user->id, $exercise_id);
        $this->calcGrades();

        return array();
    }

    public function calcGrades()
     {
        global $user;

        $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
        $test = \VipsTest::findOneBySQL('id = ?', array($this->test_id));
        if($test == null) {
            return null;
        }
        $progress = $this->getProgress();
        $progress->max_grade = $test->getExerciseCount();
        $progress->grade = 0;

        foreach ($test->getExercises() as $exercise) {
            $solution = \VipsSolution::findOneBySQL("exercise_id = ? AND user_id = ?", array($exercise->id, $user->id));
            $exercise_ref = \VipsExerciseRef::findOneBySQL("exercise_id = ?", array($exercise->id));

            $correct = $solution ? ($exercise_ref["points"]== $solution->points) : false;
            if (($assignment->type != "selftest")&&($solution != "")) {
                $correct = true;
            } 
            if ($correct) {
                $progress->grade++;
            }
        }
        return $progress;
     }

    private function vipsActivated() 
    {
        if ($this->vipsInstalled()) {
            $plugin_manager = \PluginManager::getInstance();
            $plugin_info = $plugin_manager->getPluginInfo('VipsPlugin');
            return $plugin_manager->isPluginActivated($plugin_info['id'], $this->getModel()->seminar_id);
        } else {
            return false;
        }
    }

    private function vipsVersion()
    {
        if ($this->vipsInstalled()) {
            $plugin_manager = \PluginManager::getInstance();
            $version = $plugin_manager->getPluginManifest($plugin_manager->getPlugin('VipsPlugin')->getPluginPath())["version"];
            return version_compare("1.3",$version) <= 0;
        } else {
            return false;
        }
    }

    private function vipsInstalled()
    {
        $plugin_manager = \PluginManager::getInstance();
        return $plugin_manager->getPlugin('VipsPlugin') != null ? true : false;
    }

    private function buildExercises()
    {
        global $user;

        $exercises = array();
        $available = false;
        $test = \VipsTest::findOneBySQL('id = ?', array($this->test_id));
        $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));

        $numberofex = $test->getExerciseCount();
        $exindex = 1;

        $now = time();
        $start = strtotime($assignment->start);
        $end = strtotime($assignment->end);
        $solving_allowed = ($now >= $start) && ($now <= $end);
        $solved_completely = true;

        foreach ($test->getExercises() as $exercise){
            $solution = \VipsSolution::findOneBySQL("exercise_id = ? AND user_id = ?", array($exercise->id, $user->id));
            $has_solution = $solution != null;
            $correct = false;
            $tryagain = false;
            $try_counter = 0;

            if (($assignment->type == "selftest")&& $has_solution) {
                $evaluation = $exercise->evaluate($solution);
                $correct = $evaluation['percent'] == 1;

                if(!$this->tries) {
                    $local_tries = array();
                } else {
                    $local_tries = $this->tries;
                }
                $try_counter = $local_tries[$exercise->getId()];
                $tryagain = $solution && !$correct;
            }
            if ($correct ==  false) {
                 $solved_completely = false;
            }
            $tries_left = -1;
            $courseware_block = $this->container['current_courseware'];
            $max_counter = $courseware_block->getMaxTries();
            if(!$max_counter) {
                // no max counter, do as before
                $show_corrected_solution = $correct;
            } else if ($max_counter === -1) {
                // unlimited tries to answer
                $show_corrected_solution = $correct;
            } else {
                // limited tries
                $tries_left = $max_counter - $try_counter;
                $show_corrected_solution = ($correct || (($tries_left < 1) && ($assignment->type == "selftest") ));
            }
            $tries_pl = false;
            if ($tries_left > 1) {
                $tries_pl = true;
            }
            if ( $exercise->options["feedback"] !== "") {
                $corrector_comment = $exercise->options["feedback"];
            } else {
                $corrector_comment = false;
            }

            if ( $exercise->task["answers"][0]["text"] !== "") {
                $sample_solution = $exercise->task["answers"][0]["text"];
            } else {
                $sample_solution = false;
            }
            $entry = array(
                'exercise_type'         => $exercise->getTypeName(),
                'id'                    => $exercise->getId(),
                'test_id'               => $this->test_id,
                'self_test'             => $assignment->type == "selftest",
                'exercise_sheet'        => $assignment->type == "practice",
                'show_correction'       => $assignment->type == "selftest",
                'show_solution'         => $has_solution && $show_corrected_solution,
                'title'                 => $exercise->title,
                'question'              => preg_replace('#<script(.*?)>(.*?)</script>#is', '', $exercise->getSolveTemplate($solution)->render() ),
                'single-choice'         => get_class($exercise) == "sc_exercise",
                'multiple-choice'       => get_class($exercise) == "mc_exercise",
                'solver_user_id'        => $user->cfg->getUserId(),
                'has_solution'          => $has_solution,
                'solution'              => $exercise->getCorrectionTemplate($solution)->render(),
                'solving_allowed'       => $solving_allowed,
                'number_of_exercises'   => $numberofex,
                'exercise_index'        => $exindex++,
                'correct'               => $correct,
                'tryagain'              => $tryagain,
                'exercise_hint'         => $exercise->options["hint"],
                'corrector_comment'     => $corrector_comment, 
                'sample_solution'       => $sample_solution,
                'is_corrected'          => $solution["corrected"],
                'tries_left'            => $tries_left, 
                'tries_pl'              => $tries_pl
            );
            $entry['skip_entry'] = !$entry['show_solution'] && !$entry['solving_allowed'];
            $available = !$entry['show_solution'] && !$entry['solving_allowed']; //or correction is available
            $exercises[] = $entry;
        }

        $exercises_available = false;
        foreach ($exercises as $ex) {
            if (!$ex['skip_entry']) {
                $exercises_available = true;
                break;
            }
        }
        $correction_available = false;
        foreach ($exercises as $ex) {
            if ($ex['is_corrected']) {
                $correction_available = true;
                break;
            }
        }
        return array(
            'title'                 => $test->title,
            'description'           => formatReady($test->description),
            'exercises'             => $exercises,
            'available'             => $available,
            'exercises_available'   => $exercises_available,
            'solved_completely'     => $solved_completely, 
            'isSequential'          => $this->container["current_courseware"]->getProgressionType() == "seq",
            'correction_available'  => $correction_available
        );
    }

    private function getAttrArray() 
    {
        return array(
            'test_id' => $this->test_id,
        );
    }

    public function exportProperties()
    {
        $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
        $xml = $assignment->exportXML();
        return array(
            'xml' => $xml
        );
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
        if (isset($properties['xml'])) {
            $xml = $properties['xml'];
            $result = \VipsAssignment::importXML($xml, $this->container['current_user_id'] , $this->container['cid']);
            $this->test_id = $result->test->id;
        }
        $this->save();
    }
}
