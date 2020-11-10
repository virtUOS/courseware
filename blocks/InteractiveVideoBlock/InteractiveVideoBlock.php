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
        $this->defineField('iav_source', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('iav_overlays', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('iav_stops', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('assignment_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('iav_tests', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('range_inactive', \Mooc\SCOPE_BLOCK, false);
        $this->defineField('tries',   \Mooc\SCOPE_USER, array()); // Field to count the tries
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

        $max_counter = $courseware->getMaxTriesIAV();
        if($installed && $active && $version && ($this->assignment_id != '')) {
            $selected_assignment = \VipsAssignment::find($this->assignment_id);
            $testmap = array();
            foreach (json_decode($this->iav_tests) as $test) {
                $testmap[$test->test_id] = $test->use_test;
            }
            if(!empty($selected_assignment->test->exercises)) {
                foreach ($selected_assignment->test->exercises as $exercise) {
                    if ($testmap[$exercise->getId()]) {
                        if(!$this->tries) {
                            $local_tries = array();
                        } else {
                            $local_tries = $this->tries;
                        }
                        $try_counter = $local_tries[$exercise->getId()];

                        $solution = \VipsSolution::findOneBySQL('exercise_id = ? AND user_id = ?', array($exercise->id, $user->id));
                        $has_solution = $solution != null;
                        $correct = false;
                        if ($has_solution) {
                            $evaluation = $exercise->evaluate($solution);
                            $correct = $evaluation['percent'] == 1;
                        }

                        if($max_counter != -1) {
                            $tries_left = $max_counter - $try_counter;
                            if ($tries_left < 1) {
                                $tries_left = false;
                            }
                            if ($correct || $tries_left < 1) {
                                $local_tries[$exercise->getId()] = 0;
                                $this->tries = $local_tries;
                            }
                            $no_more_tries = $try_counter >= $max_counter;
                            if ($try_counter == 0) {
                                $has_solution = false;
                            }
                        } else {
                            $tries_left = false;
                            $no_more_tries = false;
                        }

                        $rendered_solution = '';
                        if($solution != null) {
                            $rendered_solution = $exercise->getCorrectionTemplate($solution)->render();
                        }

                        $exercises[] = array(
                            'question'              => $exercise->getSolveTemplate($solution, $selected_assignment, $user->id)->render(),
                            'question_description'  => formatReady($exercise->description),
                            'title'                 => $exercise->title,
                            'id'                    => $exercise->getId(),
                            'correct'               => $correct,
                            'has_solution'          => $has_solution,
                            'solution'              => $rendered_solution,
                            'no_more_tries'         => $no_more_tries,
                            'tries_left'            => $tries_left,
                            'tries_pl'              => $tries_left != 1
                        );
                    }
                }
            }
        }
        if ($this->iav_source != '') {
            $iav_source = json_decode($this->iav_source, true);
            if (!$iav_source['external']) {
                $file = \FileRef::find($iav_source['file_id']);
                if ($file) { 
                    $iav_url = $this->isFileDownloadable($file) ? $this->getFileURL($file) : '';
                } else {
                    $iav_url = '';
                }
            } else {
                $iav_url = $iav_source['url'];
            }
        } else {
            $iav_url = '';
        }

        return array_merge($this->getAttrArray(), array(
            'exercises'  => $exercises,
            'iav_url'    => $iav_url,
            'vips14'     => $courseware->vipsVersion('1.4')
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
            $iav_file_id = $source->file_id;
            $iav_file_name = $source->file_name;
            $external_file = $source->external;
        } else {
            $iav_url = '';
            $external_file = false;
            $iav_file_id = '';
            $iav_file_name = '';
        }
        $files_arr = $this->showFiles(array($iav_file_id));
        $no_files = empty($files_arr['userfilesarray']) && empty($files_arr['coursefilesarray']) && empty($files_arr['other_user_files']) && empty($iav_file_id);


        return array_merge($this->getAttrArray(), array(
            'block_id'           => $this->_model->id,
            'has_assignments'    => !empty($assignments),
            'assignments'        => $assignments,
            'exercises'          => $exercises,
            'active'             => $active,
            'version'            => $version,
            'installed'          => $installed,
            'manage_tests_url'   => \PluginEngine::getURL('vipsplugin', array(), 'sheets'),
            'iav_url'            => $iav_url,
            'external_file'      => $external_file,
            'user_video_files'   => $files_arr['userfilesarray'],
            'course_video_files' => $files_arr['coursefilesarray'],
            'no_video_files'     => $no_files,
            'other_user_files'    => $files_arr['other_user_files']
        ));
    }

    public function preview_view()
    {

        return array('url' => json_decode($this->iav_source)->url);
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        $this->iav_source = $data['iav_source']; // json
        $this->iav_overlays = $data['iav_overlays']; // json
        $this->iav_stops = $data['iav_stops']; // json
        $this->iav_tests = $data['iav_tests']; // json
        $this->assignment_id = $data['assignment_id'];
        $this->range_inactive = $data['range_inactive'];

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
            'iav_source'    => $this->iav_source,
            'iav_overlays'  => $this->iav_overlays,
            'iav_stops'     => $this->iav_stops,
            'iav_tests'     => $this->iav_tests,
            'assignment_id' => $this->assignment_id,
            'range_inactive' => $this->range_inactive
        );
    }

    private function showFiles($file_ids)
    {
        $coursefilesarray = array();
        $userfilesarray = array();
        $course_folders = \Folder::findBySQL('range_id = ? AND folder_type NOT IN (?)', array($this->container['cid'], array('HiddenFolder', 'HomeworkFolder')));
        $hidden_folders = $this->getHiddenFolders();
        $course_folders = array_merge($course_folders, $hidden_folders);
        $user_folders = \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $other_user_files = array();

        foreach ($course_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isVideo()) && (!$ref->isLink())) {
                    $coursefilesarray[] = $ref;
                }
                $key = array_search($ref->id, $file_ids);
                if($key > -1) {
                    unset ($file_ids[$key]);
                }
            }
        }

        foreach ($user_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isVideo()) && (!$ref->isLink())) {
                    $userfilesarray[] = $ref;
                }
                if(in_array($ref->id, $file_ids)) {
                    unset ($file_ids[$key]);
                }
            }
        }

        if (empty($file_ids)) {
            $other_user_files = false;
        } else {
            foreach ($file_ids as $id) {
                $file_ref = \FileRef::find($id);
                array_push($other_user_files, $file_ref);
            }
        }

        return array('coursefilesarray' => $coursefilesarray, 'userfilesarray' => $userfilesarray, 'other_user_files' => $other_user_files);
    }

    private function getHiddenFolders()
    {
        $folders = array();

        $hidden_folders = \Folder::findBySQL('range_id = ? AND folder_type = ?', array($this->container['cid'], 'HiddenFolder'));

        foreach ($hidden_folders as $hidden_folder) {
            if($hidden_folder->data_content['download_allowed'] == 1) {
                array_push($folders, $hidden_folder);
            }
        }

        return $folders;
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

    public function getHtmlExportData()
    {
        $source = json_decode($this->iav_source);
        if (!$source->external) {
            $file_ref = new \FileRef($source->file_id);
            $file = new \File($file_ref->file_id);
            $source->file_name = $file->name;
        }

        return array(
            'iav_source'    => $source,
            'iav_overlays'  => json_decode($this->iav_overlays),
            'iav_stops'     => json_decode($this->iav_stops),
            'iav_tests'     => json_decode($this->iav_tests),
            'assignment_id' => $this->assignment_id,
            'range_inactive' => $this->range_inactive
        );
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
                'url' => $this->getFileURL($file_ref),
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
        if (isset($properties['range_inactive'])) {
            $this->range_inactive = $properties['range_inactive'];
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
                    $source->url = $this->getFileURL($file);
                    $this->iav_source = json_encode($source);

                    $this->save();
                    return array($file->id);
                }
            }
        }
    }
}
