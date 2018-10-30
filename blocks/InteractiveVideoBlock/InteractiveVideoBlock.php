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
        $this->defineField('iav_source', \Mooc\SCOPE_BLOCK, "");
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
            if(!empty($selected_assignment->test->exercises)) {
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
                            'question_description'=> formatReady($exercise->description),
                            'title' => $exercise->title,
                            'id' => $exercise->getId(),
                            'correct' => $correct,
                            'has_solution' => $has_solution,
                            'solution' => $exercise->getCorrectionTemplate($solution)->render(),
                        );
                    }
                }
            }
        }
        if ($this->iav_source != '') {
            $iav_url = json_decode($this->iav_source)->url;
        } else {
            $iav_url = '';
        }

        return array_merge($this->getAttrArray(), array(
            'exercises'         => $exercises,
            'iav_url'           => $iav_url
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
                    'question_description'  => formatReady($exercise->description),
                    'current_assignment'    => $this->assignment_id === $assignment->id
                );
            }
            $exercises = array();
            $selected_assignment = \VipsAssignment::find($this->assignment_id);
            if (($this->assignment_id != '') &&(!empty($selected_assignment->test->exercises))) {
                foreach ($selected_assignment->test->exercises as $exercise) {
                    $exercises[] = array(
                        'question' => $exercise->getSolveTemplate($solution, $assignment, $user->id)->render(),
                        'question_description' => formatReady($exercise->description),
                        'title' => $exercise->title,
                        'id' => $exercise->getId()
                    );
                }
            }
        }

        if ($this->iav_source != '') {
            $source = json_decode($this->iav_source);
            $iav_url = $source->url;
            $external_file = $source->external;
        } else {
            $iav_url = '';
            $external_file = false;
        }
        $video_files = $this->showFiles();
        if (empty($video_files)) {
            $video_files = false;
        }

        return array_merge($this->getAttrArray(), array(
            'block_id'          => $this->_model->id,
            'has_assignments'   => !empty($assignments),
            'assignments'       => $assignments, 
            'exercises'         => $exercises, 
            'active'            => $active, 
            'version'           => $version,
            'installed'         => $installed,
            'manage_tests_url'  => \PluginEngine::getURL('vipsplugin', array(), 'sheets'),
            'iav_url'           => $iav_url,
            'external_file'     => $external_file,
            'video_files'       => $video_files
        ));
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        $this->iav_source = $data['iav_source']; // json
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
            'iav_source'       => $this->iav_source,
            'iav_overlays'  => $this->iav_overlays,
            'iav_stops'     => $this->iav_stops,
            'iav_tests'     => $this->iav_tests,
            'assignment_id' => $this->assignment_id
        );
    }

    private function showFiles()
    {
        $filesarray = array();
        $folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));

        foreach ($folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if ($ref->isVideo())  {
                    $filesarray[] = $ref;
                }
            }
        }

        return $filesarray;
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        if ($this->assignment_id != "") {
            $assignment = \VipsAssignment::find($this->assignment_id);
            $vips_xml = $assignment->exportXML();

            return array_merge($this->getAttrArray(), array('vips_xml' => $vips_xml));
        }

        return $this->getAttrArray();
    }

    public function getFiles()
    {
        $source = json_decode($this->iav_source);
        $files = array();

        if (!$source->external) {
            $file_ref = new \FileRef($source->file_id);
            $file = new \File($file_ref->file_id);

            array_push( $files, array (
                'id' => $file_ref->id,
                'name' => $file_ref->name,
                'description' => $file_ref->description,
                'filename' => $file->name,
                'filesize' => $file->size,
                'url' => $file->getURL(),
                'path' => $file->getPath()
            ));
        }

        return $files;
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
        if (isset($properties['iav_source'])) {
            $this->iav_source = $properties['iav_source'];
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
        if (isset($properties['vips_xml'])) {
            $xml = $properties['vips_xml'];
            $result = \VipsAssignment::importXML($xml, $this->container['current_user_id'] , $this->container['cid']);
            $this->assignment_id = $result->id;
            $test_id = $result->test_id;
            $test = \VipsTest::find($test_id);
            $exercises = $test->getExercises();
            $iav_tests = json_decode($this->iav_tests);
            foreach ($exercises as $exercise) {
                foreach ($iav_tests as &$iav_test) {
                    if ($exercise->title == $iav_test->title) {
                        $iav_test->test_id = $exercise->id;
                    }
                }
            }
            $this->iav_tests = json_encode($iav_tests);
        }

        $this->save();
    }

    private function setFileId($file_name)
    {
        return;
    }

    public function importContents($contents, array $files)
    {
        $source = json_decode($this->iav_source);
        if (!$source->external) {
            foreach($files as $file){
                if ($source->file_name == $file->name) {
                    $source->file_id = $file->id;
                    $source->url = $file->getDownloadURL();
                }
            }
        $this->iav_source = json_encode($source);
        }

        $this->save();
    }
}
