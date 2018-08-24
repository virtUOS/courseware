<?php
namespace Mooc\UI\InteractiveVideoBlock;

use Mooc\UI\Block;
use Mooc\DB\Block as DBBlock;

class InteractiveVideoBlock extends Block
{
    const NAME = 'Interactive Video';
    const BLOCK_CLASS = 'interaction';
    const DESCRIPTION = 'Spielt ein mit Interaktionen angereichertes Video ab';

    public function initialize()
    {
        $this->defineField('iav_url', \Mooc\SCOPE_BLOCK, "");
        $this->defineField('iav_overlays', \Mooc\SCOPE_BLOCK, "");
        $this->defineField('iav_stops', \Mooc\SCOPE_BLOCK, "");
        $this->defineField('assignment_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('iav_tests', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        global $user;

        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $courseware = $this->container['current_courseware'];
        $installed = $courseware->vipsInstalled();
        $active = $courseware->vipsActivated();
        $version = $courseware->vipsVersion();
        $exercises = array();
        if($installed && $active && $version && ($this->assignment_id != '')) {
            $selected_assignment = \VipsAssignment::find($this->assignment_id);
            $testmap = array();
            foreach (json_decode($this->iav_tests) as $test) {
                $testmap[$test->test_id] = $test->use_test;
            }
            foreach ($selected_assignment->test->exercises as $exercise) {
                if ($testmap[$exercise->getId()]) {
                    $solution = \VipsSolution::findOneBySQL('exercise_id = ? AND user_id = ?', array($exercise->id, $user->id));
                    $has_solution = $solution != null;
                    $correct = false;
                    if ($has_solution) {
                        $evaluation = $exercise->evaluate($solution);
                        $correct = $evaluation['percent'] == 1;
                    }
                    $exercises[] = array(
                        'question' => $exercise->getSolveTemplate($solution, $assignment, $user->id)->render(),
                        'title' => $exercise->title,
                        'id' => $exercise->getId(),
                        'correct' => $correct,
                        'has_solution' => $has_solution,
                        'solution' => $exercise->getCorrectionTemplate($solution)->render(),
                    );
                }
            }
        }
        return array_merge($this->getAttrArray(), array(
            'exercises'         => $exercises
        ));
    }

    public function author_view()
    {
        global $user;
        $this->authorizeUpdate();
        $courseware = $this->container['current_courseware'];
        $installed = $courseware->vipsInstalled();
        $active = $courseware->vipsActivated();
        $version = $courseware->vipsVersion();

        $assignments = array();
        if($installed && $active && $version) {
            $stored_assignments = \VipsAssignment::findBySQL( 'course_id = ? and type = ?', array($this->_model->course->id, 'selftest'));
            foreach ($stored_assignments as $assignment) {
                $assignments[] = array(
                    'id'                    => $assignment->id,
                    'name'                  => $assignment->test->title,
                    'created'               => isset($assignment->test->created) ? date('d.m.Y', strtotime($assignment->test->created)) : '',
                    'exercises_count'       => count($assignment->test->exercises),
                    'current_assignment'    => $this->assignment_id === $assignment->id
                );
            }
            $exercises = array();
            $selected_assignment = \VipsAssignment::find($this->assignment_id);
            if ($this->assignment_id != '') {
                foreach ($selected_assignment->test->exercises as $exercise) {
                    $exercises[] = array(
                        'question' => $exercise->getSolveTemplate($solution, $assignment, $user->cfg->getUserId())->render(),
                        'title' => $exercise->title,
                        'id' => $exercise->getId()
                    );
                }
            }
        }

        return array_merge($this->getAttrArray(), array(
            'block_id'          => $this->_model->id,
            'has_assignments'   => !empty($assignments),
            'assignments'       => $assignments, 
            'exercises'         => $exercises, 
            'active'            => $active, 
            'version'           => $version,
            'installed'         => $installed,
            'manage_tests_url'  => \PluginEngine::getURL('vipsplugin', array(), 'sheets')
        ));
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        $this->iav_url = $data['iav_url']; // url
        $this->iav_overlays = $data['iav_overlays']; // json 
        $this->iav_stops = $data['iav_stops']; // json 
        $this->iav_tests = $data['iav_tests']; // json 
        $this->assignment_id = $data['assignment_id'];

        return $this->getAttrArray();
    }
    
    public function exercise_submit_handler($data)
    {
        parse_str($data, $requestParams);
        $requestParams = studip_utf8decode($requestParams);
        $exercise_id = $requestParams['exercise_id'];

        $assignment = \VipsAssignment::find($this->assignment_id);

        check_exercise_access($exercise_id, $assignment->id);
        $exercise = \Exercise::find($exercise_id);

        $start = $assignment->start;
        $end = $assignment->end;
        $now = date('Y-m-d H:i:s');

        // not yet started or already ended
        if ($start > $now || $now > $end) {
            throw new \Exception(_cw('Das Aufgabenblatt kann zur Zeit nicht bearbeitet werden.'));
        }
        $solution = $exercise->getSolutionFromRequest($requestParams);

        return $assignment->storeSolution($solution);
    }

    public function getVipsTests_handler(array $data)
    {
        $assignment_id = $data['assignment_id'];
        $assignment = \VipsAssignment::find($assignment_id);
        $test = $assignment->test;
        $exercises = array();
        foreach ($test->getExercises() as $exercise){
            array_push($exercises, '{"id":"'.$exercise->id.'", "title" :"'.$exercise->title.'"}');
        }

        return ($exercises);
    }

    public function watched_handler($data)
    {
        $this->setGrade(1);

        return array();
    }

    private function getAttrArray()
    {
        return array(
            'iav_url'       => $this->iav_url,
            'iav_overlays'  => $this->iav_overlays,
            'iav_stops'     => $this->iav_stops,
            'iav_tests'     => $this->iav_tests,
            'assignment_id' => $this->assignment_id
        );
    }
    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return $this->getAttrArray();
    }

    public function getFiles()
    {
        //TODO if file from StudIP filesystem
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/interactivevideo/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/interactivevideo/interactivevideo-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['iav_url'])) {
            $this->iav_url = $properties['iav_url'];
        }
        if (isset($properties['iav_overlays'])) {
            $this->iav_overlays = $properties['iav_overlays'];
        }
        if (isset($properties['iav_stops'])) {
            $this->iav_stops = $properties['iav_stops'];
        }
        if (isset($properties['iav_tests'])) {
            $this->iav_tests = $properties['iav_tests'];
        }

        $this->save();
    }

    private function setFileId($file_name)
    {
        return;
    }

    public function importContents($contents, array $files)
    {
        // TODO if file from StudIP filesystem -> set correct iav_url
    }
}
