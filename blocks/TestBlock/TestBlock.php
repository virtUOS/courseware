<?php
namespace Mooc\UI\TestBlock;

use Mooc\UI\Block;

/**
 * @author Ron Lucke <rlucke@uos.de>
 */

class TestBlock extends Block 
{
    const NAME = 'Quiz';
    const BLOCK_CLASS = 'interaction';
    const DESCRIPTION = 'Stellt ein Aufgabenblatt aus Vips zur Verfügung';
    const HINT = 'Für diesen Block muss das Vips Plugin aktiviert sein';

    function initialize()
    {
        $this->defineField('test_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('assignment_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('tries',   \Mooc\SCOPE_USER, array()); // Field to count the tries
    }

    public static function getSubTypes()
    {
        return array(
            'selftest' => _cw('Selbsttest'),
            'practice' => _cw('Übung'),
        );
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $courseware = $this->container['current_courseware'];
        if (!$installed = $courseware->vipsInstalled()) {
            return compact('installed');
        }
        if (!$active = $courseware->vipsActivated()) {
            return array('active' => $active, 'installed'=> $installed);
        }
        if (!$version = $courseware->vipsVersion()) {
            return array('active' => $active, 'version'=> $version, 'installed'=> $installed);
        }
        $this->calcGrades();
        $subtype =  $this->_model->sub_type;

        if ($this->assignment_id == "") {
            if ($this->test_id != "") {
                $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
            } else {
                $assignment = null;
            }
        } else {
            $assignment = \VipsAssignment::find($this->assignment_id);
        }

        $type_mismatch = !($assignment->type ==  $subtype);
        if ($assignment->type == null) {
            return array(
                'exercises'     => false,
                'typemismatch'  => false,
                'active'        => $active, 
                'version'       => $version,
                'installed'     => $installed,
                'vips14'        => $courseware->vipsVersion('1.4')
            );
        }
        if ($type_mismatch) {
            return array(
                'exercises'     => false,
                'typemismatch'  => true,
                'active'        => $active, 
                'version'       => $version,
                'installed'     => $installed,
                'vips14'        => $courseware->vipsVersion('1.4')
            );
        }

        return array_merge($this->getAttrArray(), array(
            'active' => $active,
            'version' => $version,
            'installed' => $installed,
            'vips14' => $courseware->vipsVersion('1.4')
        ), $this->buildExercises());
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $courseware = $this->container['current_courseware'];
        if (!$installed = $courseware->vipsInstalled()) {
            return compact('installed');
        }
        if (!$active = $courseware->vipsActivated()) {
            return array('active' => $active, 'installed'=> $installed);
        }
        if (!$version = $courseware->vipsVersion()) {
            return array('active' => $active, 'version'=> $version, 'installed'=> $installed);
        }
        $subtype =  $this->_model->sub_type;
        $stored_assignments = \VipsAssignment::findBySQL( 'course_id = ? and type = ?', array($this->_model->course->id, $subtype));

        $assignments = array();
        foreach ($stored_assignments as $assignment) {
            $assignments[] = array(
                'id'                    => $assignment->id,
                'name'                  => $assignment->test->title,
                'created'               => isset($assignment->test->created) ? date('d.m.Y', strtotime($assignment->test->created)) : '',
                'exercises_count'       => count($assignment->test->exercises),
                'current_assignment'    => $this->assignment_id === $assignment->id
            );
        }

        return array_merge($this->getAttrArray(), array( 
            'has_assignments'   => !empty($assignments),
            'type'              => $this->getSubTypes()[$subtype],
            'assignments'       => $assignments, 
            'active'            => $active, 
            'version'           => $version,
            'installed'         => $installed,
            'manage_tests_url'  => \PluginEngine::getURL('vipsplugin', array(), 'sheets')
            ));
    }

    public function preview_view()
    {
        $assignment = \VipsAssignment::find($this->assignment_id);

        return array('name' => $assignment->test->title);
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset ($data['assignment_id'])) {
            $this->assignment_id = (string) $data['assignment_id'];
        } 

        return;
    }

    public function exercise_submit_handler($data)
    {
        global $user;

        $exercise_id = $data['exercise_id'];
        $exercise_index = $data['exercise_index'];

        if ($this->assignment_id == "") {
            if ($this->test_id != "") {
                $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
            } else {
                $assignment = null;
            }
        } else {
            $assignment = \VipsAssignment::find($this->assignment_id);
        }

        check_exercise_access($exercise_id, $assignment->id);
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

        $files = null;

        if($data['files'] != '') {
            $files['upload']['name'] =  [];
            $files['upload']['type'] =  [];
            $files['upload']['tmp_name'] = [];
            $files['upload']['size'] = [];
            $files['upload']['user_id'] = [];
            $files_array = $data['files'];
            foreach($files_array as $file) {
                $file_name = $file['name'];
                $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
                mkdir($tempDir);
                file_put_contents($tempDir.'/'.$file_name, base64_decode(explode('base64,', $file['file'])[1]));

                array_push($files['upload']['name'], $file_name);
                array_push($files['upload']['type'], $file['type']);
                array_push($files['upload']['tmp_name'], $tempDir.'/'.$file_name);
                array_push($files['upload']['size'], filesize($tempDir.'/'.$file_name));
                array_push($files['upload']['user_id'], $user->id);
            }
        }

        $solution = $exercise->getSolutionFromRequest($data, $files);
        if ($this->container['current_user']->isNobody()) {
            if ($assignment->type == "selftest") {
                $assignment->correctSolution($solution);
                return array(
                    'is_nobody'      => true, 
                    'hasSolution'    => true, 
                    'solution'       => $exercise->getCorrectionTemplate($solution)->render(),
                    'exercise_index' => $exercise_index,
                    'title'          => $exercise->title
                );
            } else {
                return array(
                    'is_nobody'      => true, 
                    'hasSolution'    => true, 
                    'solution'       => _cw('Diese Aufgabe können nur eingeloggte Nutzer abgeben.'),
                    'exercise_index' => $exercise_index,
                    'title'          => $exercise->title
                );
            }
        }

        $file_upload_failed = false;
        if($data['files'] != null) {
            $solution_files = [];
            foreach ($solution->files as $vips_file) {
                $solution_files[] = $vips_file->toArray()['name'];
            }
            foreach($files_array as $file) {
                if ( !in_array($file['name'], $solution_files)) {
                    $file_upload_failed = true;
                }
            }
            $this->deleteRecursively($tempDir);
        }

        $assignment->storeSolution($solution);
        $progress = $this->calcGrades();

        return array('grade' => $progress->max_grade > 0 ? $progress->grade / $progress->max_grade : 0, 'file_upload_failed' => $file_upload_failed);
    }

    public function exercise_reset_handler($data)
    {
        $user = $this->container['current_user'];
        parse_str($data, $requestData);
        $exercise_id = $requestData['exercise_id'];

        if($this->assignment_id == "") {
            if ($this->test_id != "") {
                $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
                $test_id = $this->test_id;
            }
        } else {
            $assignment = \VipsAssignment::find($this->assignment_id);
            $test_id = $assignment->test->id;
        }

        check_test_access($test_id);
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

        if($this->assignment_id == "") {
            if ($this->test_id != "") {
                $test = \VipsTest::findOneBySQL('id = ?', array($this->test_id));
                $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
            }
        } else {
            $assignment = \VipsAssignment::find($this->assignment_id);
            $test = $assignment->test;
        }

        if($test == null) {
            return null;
        }
        $progress = $this->getProgress();
        $progress->max_grade = $test->getExerciseCount();
        $progress->grade = 0;

        foreach ($test->getExercises() as $exercise) {
            $solution = \VipsSolution::findOneBySQL('exercise_id = ? AND user_id = ?', array($exercise->id, $user->id));
            $exercise_ref = \VipsExerciseRef::findOneBySQL('exercise_id = ?', array($exercise->id));
            $correct = $solution ? ($exercise_ref['points']== $solution->points) : false;
            if (($assignment->type != 'selftest')&&($solution != '')) {
                $correct = true;
            } 
            if ($correct) {
                $progress->grade++;
            }
        }

        return $progress;
     }

    private function buildExercises()
    {
        global $user;

        $exercises = array();
        $available = false;
        if($this->assignment_id == "") {
            if ($this->test_id != "") {
                $test = \VipsTest::findOneBySQL('id = ?', array($this->test_id));
                $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
            }
        } else {
            $assignment = \VipsAssignment::find($this->assignment_id);
            $test = $assignment->test;
        }
        $numberofex = $test->getExerciseCount();
        $exindex = 1;
        $now = time();
        $start = strtotime($assignment->start);
        $end = strtotime($assignment->end);
        $solving_allowed = ($now >= $start) && ($now <= $end);
        $solved_completely = true;

        foreach ($test->getExercises() as $exercise){
            $solution = \VipsSolution::findOneBySQL('exercise_id = ? AND user_id = ?', array($exercise->id, $user->id));
            $has_solution = $solution != null;
            $correct = false;
            $tryagain = false;
            $try_counter = 0;

            if (($assignment->type == 'selftest')&& $has_solution) {
                $evaluation = $exercise->evaluate($solution);
                $correct = $evaluation['percent'] == 1;
                if(!$this->tries) {
                    $local_tries = array();
                } else {
                    $local_tries = $this->tries;
                }
                $try_counter = $local_tries[$exercise->getId()];
                $tryagain = $solution && !$correct;
                $tb_exercise = false;

                if ($exercise->type == "tb_exercise") {
                    $tb_exercise = true;
                    $tryagain = false;
                    $correct = true;
                }
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
                $show_corrected_solution = ($correct || (($tries_left < 1) && ($assignment->type == 'selftest') ));
            }
            $tries_pl = false;
            if ($tries_left > 1) {
                $tries_pl = true;
            }

            if($has_solution && $solution['corrected']) {
                $corrector_comment = $solution['corrector_comment'];
            }

            if ( $exercise->task['answers'][0]['text'] !== '') {
                $sample_solution = $exercise->task['answers'][0]['text'];
            } else {
                $sample_solution = false;
            }

            if ($tries_left == -1) {
                $tries_left = false;
            }

            if (in_array($exercise->type, array('lt_exercise', 'tb_exercise', 'cloze_exercise')) && ($exercise->options['lang'] != null)) {
                $character_picker = true;
            } else {
                $character_picker = false;
            }

            if ($solution != null) {
                $rendered_solution = $exercise->getCorrectionTemplate($solution)->render();
            } else {
                $rendered_solution = '';
            }

            $entry = array(
                'exercise_type'       => $exercise->type,
                'tb_exercise'         => $tb_exercise,
                'id'                  => $exercise->getId(),
                'test_id'             => $this->test_id,
                'self_test'           => $assignment->type == 'selftest',
                'exercise_sheet'      => $assignment->type == 'practice',
                'show_correction'     => $assignment->type == 'selftest',
                'show_solution'       => $has_solution && $show_corrected_solution,
                'title'               => $exercise->title,
                'question'            => $exercise->getSolveTemplate($solution, $assignment, $user->id)->render(),
                'question_description'=> formatReady($exercise->description),
                'single-choice'       => get_class($exercise) == 'sc_exercise',
                'multiple-choice'     => get_class($exercise) == 'mc_exercise',
                'solver_user_id'      => $user->id,
                'has_solution'        => $has_solution,
                'solution'            => $rendered_solution,
                'solving_allowed'     => $solving_allowed,
                'number_of_exercises' => $numberofex,
                'exercise_index'      => $exindex++,
                'correct'             => $correct,
                'tryagain'            => $tryagain,
                'exercise_hint'       => $exercise->options['hint'],
                'corrector_comment'   => $corrector_comment, 
                'sample_solution'     => $sample_solution,
                'is_corrected'        => $solution['corrected'] && ($assignment->options['released'] == 2),
                'tries_left'          => $tries_left, 
                'tries_pl'            => $tries_pl,
                'character_picker'    => $character_picker
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
            'title'                => $test->title,
            'description'          => formatReady($test->description),
            'exercises'            => $exercises,
            'available'            => $available,
            'exercises_available'  => $exercises_available,
            'solved_completely'    => $solved_completely, 
            'isSequential'         => $this->container['current_courseware']->getProgressionType() == 'seq',
            'correction_available' => $correction_available
        );
    }

    private function getAttrArray() 
    {
        return array(
            'test_id' => $this->test_id,
            'assignment_id' => $this->assignment_id,
        );
    }

    public function exportProperties()
    {
        if ($this->assignment_id == "") {
            $courseware = $this->container['current_courseware'];
            if ( ($this->test_id == "") || !($courseware->vipsVersion()) ){
                return;
            }
            $assignment = \VipsAssignment::findOneBySQL('test_id = ?', array($this->test_id));
        } else {
            $assignment = \VipsAssignment::find($this->assignment_id);
        }

        if ($assignment == null) {
                return;
        }
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
            $this->assignment_id = $result->id;
        }
        $this->save();
    }
}
