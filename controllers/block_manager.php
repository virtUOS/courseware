<?php

use Mooc\DB\Block as dbBlock;
use Mooc\Import\XmlImport;
use Mooc\Export\XmlExport;
use Mooc\Export\Validator\XmlValidator;

/**
 * Controller to manage Courseware Blocks
 *
 * @author Ron Lucke <lucke@elan-ev.de>
 */
class BlockManagerController extends CoursewareStudipController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (Navigation::hasItem('/course/mooc_courseware/block_manager')) {
            Navigation::activateItem('/course/mooc_courseware/block_manager');
        }
        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css');
        PageLayout::addScript($this->plugin->getPluginURL().'/assets/js/ziploader/zip-loader.min.js');
    }

    public function index_action()
    {
        if (!$this->container['current_user']->canCreate($this->container['current_courseware'])) {
            throw new Trails_Exception(401);
        }
        $this->cid = Request::get('cid');
        $this->errors = [];
        $this->warnings = [];
        $this->successes = [];
        $this->remote_courses = [];
        $plugin_manager = PluginManager::getInstance();

        foreach(CourseMember::findBySQL('user_id = ? AND status = ?', array($this->container['current_user']['user_id'], 'dozent')) as $seminar_user_obj) {
            if ($this->cid != $seminar_user_obj->Seminar_id) {
                $remote_course = Course::find($seminar_user_obj->Seminar_id);
                if($remote_course != null) {
                    $plugin_info = $plugin_manager->getPluginInfo($this->plugin->getPluginName());
                    if ($plugin_manager->isPluginActivated($plugin_info['id'], $seminar_user_obj->Seminar_id)) {
                        $semester = Semester::findOneBySQL('beginn = ?', array($remote_course->start_time));
                        $sem_name = (string)$semester->name;
                        if(!isset($this->remote_courses[$sem_name])) {
                            $this->remote_courses[$sem_name] = [];
                        }
                        array_push($this->remote_courses[$sem_name], array('id' => $seminar_user_obj->Seminar_id, 'name' => (string) $remote_course->getFullname()));
                    }
                }
            }
        }

        $this->block_map = json_encode($this->buildBlockMap());

        $grouped = $this->getGrouped($this->cid);

        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $this->courseware);
        $this->courseware_json = json_encode($this->courseware);
        $this->remote_courses_json = json_encode($this->remote_courses);
        $this->lang = getUserLanguage($this->container['current_user']->id);
        $this->courseware_export_url = PluginEngine::getURL('courseware/export', compact('cid'), true);
        $this->courseware_import_url = PluginEngine::getURL('courseware/import', compact('cid'), true);
        $this->courseware_export_html_url = PluginEngine::getURL('courseware/cwp_export', compact('cid'), true);
    }

    public function get_courseware_action()
    {
        $request = trim(file_get_contents("php://input"));
        $decoded_request = json_decode($request, true);
        $cid = $decoded_request['cid'];
        $this->lang = getUserLanguage($this->container['current_user']->id);
        $grouped = $this->getGrouped($cid);
        $courseware = current($grouped['']);
        $this->buildTree($grouped, $courseware);

        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($courseware));
    }

    public function get_remote_course_action()
    {
        $remote_cid = Request::get('remote_cid');
        $courseware = $this->getRemoteCourseware($remote_cid);

        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($courseware));
    }

    public function add_structure_action()
    {
        $request = trim(file_get_contents("php://input"));
        $decoded_request = json_decode($request, true);
        $parent_id = $decoded_request['parent'];
        $title = $decoded_request['title'];
        $type = $decoded_request['type'];
        $cid = $decoded_request['cid'];

        $block = new \Mooc\DB\Block();
        $block->setData(array(
            'seminar_id' => $cid,
            'parent_id' => $parent_id,
            'type' => $type,
            'title' => $title,
            'position' => $block->getNewPosition($parent_id)
        ));

        $block->store();
        $block->setData(array(
            'approval' => $block->parent->approval
        ));
        $block->store();
        $block = $block->toArray();
        $block['childType'] = $this->getSubElement($block['type']);
        $block['isStrucutalElement'] = true;
        $block['isBlock'] = false;

        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($block));
    }

    private function getGrouped($cid, $remote = false)
    {
        $grouped = array_reduce(
            dbBlock::findBySQL('seminar_id = ? ORDER BY id, position', array($cid)),
            function($memo, $item) use($remote) {
                $arr = $item->toArray();
                $arr['isRemote'] = false;
                if ($remote) {
                    $arr['isRemote'] = true;
                }
                $arr['isStrucutalElement'] = true;
                $arr['childType'] = $this->getSubElement($arr['type']);
                if (!$item->isStructuralBlock()) {
                    $arr['isStrucutalElement'] = false;
                    $arr['isBlock'] = true;
                    $ui_block = $this->plugin->getBlockFactory()->makeBlock($item);
                    $arr['ui_block'] = $ui_block;
                    $arr['readable_name'] = $this->getI18NBlockName($ui_block::NAME);
                    $arr['preview'] = '';
                    if(method_exists($ui_block, 'preview_view')) {
                        $arr['preview'] = $ui_block->render('preview', array());
                    }
                }
                $arr['publication_date'] = $arr['publication_date'] * 1000;
                $arr['withdraw_date'] = $arr['withdraw_date'] * 1000;

                $arr['isPublished'] = $item->isPublished();
                $memo[$item->parent_id][] = $arr;
                return $memo;
            },
            array());

        return $grouped;
    }

    private function getSubElement($type) {
        $sub_element = null;
        switch($type) {
            case 'Courseware':
                $sub_element = 'Chapter';
                break;
            case 'Chapter':
                $sub_element = 'Subchapter';
                break;
            case 'Subchapter':
                $sub_element = 'Section';
                break;
            case 'Section':
                $sub_element = 'Block';
                break;
            case 'Block':
            default:
        }

        return $sub_element;
    }

    private function getRemoteCourseware($cid)
    {
        $grouped = $this->getGrouped($cid, true);

        $remote_courseware = current($grouped['']);
        $this->buildTree($grouped, $remote_courseware);

        return $remote_courseware;
    }

    public function get_course_users_action()
    {
        $cid = Request::get('cid');
        $course_members =
            CourseMember::findByCourseAndStatus($cid, 'user') +
            CourseMember::findByCourseAndStatus($cid, 'autor');

        $users_json = [];
        foreach($course_members as $member) {
           array_push($users_json, [
               'user_id'   => $member->user_id,
               'firstname' => $member->vorname,
               'lastname'  => $member->nachname,
               'username'  => $member->username,
               'perm'      => $member->status
            ]);
        }

        usort($users_json, function($a, $b) { return strcmp($a['lastname'], $b['lastname']);});

        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($users_json));
    }

    public function get_course_groups_action()
    {
        $cid = Request::get('cid');
        $groups = Statusgruppen::findAllByRangeId($cid);
        $json_groups = [];
        foreach($groups as $group) {
            array_push($json_groups, ['id'=>$group->id, 'name'=> (string)$group->name]);
        }
        usort($json_groups, function($a, $b) { return strcmp($a['name'], $b['name']);});

        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($json_groups));
    }

    public function get_element_approval_list_action() {
        $request = trim(file_get_contents("php://input"));
        $decoded_request = json_decode($request, true);
        $bid = $decoded_request['bid'];
        $type = $decoded_request['type'];

        $block = dbBlock::find($bid);
        $list = $block->getApprovalList() ?: [];

        // create default settings if necessary
        if (!$list['settings']) {
            $list['settings'] = [
                'defaultRead'   => true
            ];
        }

        $list['settings']['caption_autor'] = get_title_for_status('autor', 2);
        $list['settings']['caption_user']  = get_title_for_status('user', 2);

        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($list));
    }

    public function set_element_approval_list_action() {
        $request = trim(file_get_contents("php://input"));
        $decoded_request = json_decode($request, true);
        $bid = $decoded_request['bid'];
        $list = $decoded_request['list'];

        $block = dbBlock::find($bid);

        $block->setApprovalList($list);
        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(true);
    }

    private function buildBlockMap()
    {
        $block_map = [];

        foreach($this->plugin->getBlockFactory()->getContentBlockClasses() as $type) {
            $className = '\Mooc\UI\\'.$type.'\\'.$type;
            $nameConstant = $className.'::NAME';
             if (defined($nameConstant)) {
                $block_map[$type] = _cw(constant($nameConstant));
            } else {
                $block_map[$type] = $type;
            }
        }

        $block_map['chapter'] = 'Kapitel';
        $block_map['subchapter'] = 'Unterkapitel';
        $block_map['section'] = 'Abschnitt';

        return $block_map;
    }

    private function buildTree($grouped, &$root)
    {
        $this->addChildren($grouped, $root);
        if ($root['type'] !== 'Section') {
            if (!empty($root['children'])) {
                foreach($root['children'] as &$child) {
                    $this->buildTree($grouped, $child);
                }
            }
        } else {
            $root['children'] = $this->addChildren($grouped, $root);
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = $grouped[$parent['id']];
        if ($parent['children'] != null) {
            usort($parent['children'], function($a, $b) {
                return $a['position'] - $b['position'];
            });
        }

        return $parent['children'];
    }

    public function import_complete_archive_action()
    {
        PluginEngine::getPlugin('CoreForum');

        $request = trim(file_get_contents("php://input"));
        $decoded_request = json_decode($request, true);
        $cid = $decoded_request['cid'];
        $file_data = $decoded_request['fileData'];
        $zip_file = base64_decode(explode('base64,', $file_data['file'])[1]);

        $tempDirZip = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDirZip);
        file_put_contents($tempDirZip.'/'.$file_data['name'], $zip_file);
        $tempDirFiles = $this->extractArchive($tempDirZip.'/'.$file_data['name']);
        $this->deleteRecursively($tempDirZip);
        if (!$tempDirFiles) {
            return;
        }
        $xml_file = $tempDirFiles.'/data.xml';
        if (!is_file($xml_file)) {
            return ;
        }

        $xml = file_get_contents($xml_file);

        if ($this->validateUploadFile($xml)) {
            $import_folder = $this->createImportFolder($cid);
            //$courseware = $this->container['current_courseware'];
            $courseware = $this->plugin->getBlockFactory()->makeBlock(dbBlock::findCourseware($cid));
            $importer = new XmlImport($this->plugin->getBlockFactory());
            try {
                $importer->import($tempDirFiles, $courseware, $import_folder);
            } catch (Exception $e){
                $this->errors[] = $e;
            }
        }

        $this->deleteRecursively($tempDirFiles);

        $grouped = $this->getGrouped($cid);
        $courseware = current($grouped['']);
        $this->buildTree($grouped, $courseware);
        $courseware = json_encode($courseware);

        $this->response->add_header('Content-Type', 'application/json');
        $answer = json_encode(['errors' => $this->errors, 'successes' => $this->successes, 'courseware' => $courseware]);
        $this->render_text($answer);
    }

    private function extractArchive($filename) {
        if(!$filename) {
            $this->errors[] = _cw('Es wurde kein Import-Archiv hochgeladen');

            return false;
        }
        // create a temporary directory
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);
        $extracted = Studip\ZipArchive::extractToPath($filename, $tempDir);
        if (!$extracted) {
            $this->errors[] = _cw('Das Import-Archiv ist beschädigt');

            return false;
        }

        return $tempDir;
    }

    private function createImportFolder($cid = null)
    {
        if($cid == null) {
            $root_folder = Folder::findTopFolder($GLOBALS['SessionSeminar']);
        } else {
            $root_folder = Folder::findTopFolder($cid);
        }
        $parent_folder = FileManager::getTypedFolder($root_folder->id);

        // create new folder for import
        $request = array('name' => 'Courseware-Import '.date("d.m.Y", time()), 'description' => 'folder for imported courseware content');
        $new_folder = new StandardFolder();
        $new_folder->setDataFromEditTemplate($request);
        $new_folder->user_id = User::findCurrent()->id;
        $courseware_folder = $parent_folder->createSubfolder($new_folder);

        return FileManager::getTypedFolder($courseware_folder->id);
    }

    private function validateUploadFile($xml)
    {
        $validator = new XmlValidator($this->plugin->getBlockFactory());
        $validationErrors = $validator->validate($xml);

        if (count($validationErrors) > 0) {
            foreach ($validationErrors as $validationError) {
                switch ($validationError->code){
                    case 1866: // attr temp-id is allowed
                        if(strpos($validationError->message, 'temp-id') < 0){
                            $this->errors[] = $validationError->message;
                        }
                        break;
                    case 1878: // handle unknown blocks
                        $this->warnings[] = $validationError->message;
                        break;
                    case 1824: // invalid filesize - no int value
                    case 1831: // empty filename
                        if (strpos($validationError->message, 'file') > -1) {
                            $this->warnings[] = $validationError->message;
                        } else {
                            $this->errors[] = $validationError->message;
                        }
                        break;
                    default:
                        $this->errors[] = $validationError->message;
                }
            }
            if (!empty($this->warnings)){
                array_unshift($this->warnings, _cw('Es konnten möglicherweise nicht alle Blöcke importiert werden'));
            }
            if (!empty($this->errors)){
                array_unshift($this->errors, _cw('Die Datendatei data.xml enthält kein valides XML'));

                return false;
            }
        }

        return true;
    }

    public function store_changes_vue_action()
    {
        PluginEngine::getPlugin('CoreForum');

        $request = trim(file_get_contents("php://input"));
        $decoded_request = json_decode($request, true);
        $cid = $decoded_request['cid'];
        $import = $decoded_request['import'];
        $remote = $decoded_request['remote'];
        $import_xml = $decoded_request['importXML'];
        $chapter_list = json_decode($decoded_request['chapterList'], true);
        $subchapter_list = json_decode($decoded_request['subchapterList'], true);
        $section_list = json_decode($decoded_request['sectionList'], true);
        $block_list = json_decode($decoded_request['blockList'], true);

        $courseware = dbBlock::findCourseware($cid);
        $changes = false;
        $this->successes[] = $chapter_list;
        if (!$import) {
            foreach(array($subchapter_list, $section_list, $block_list) as $list) {
                foreach((array)$list as $key => $value) {
                    $parent = dbBlock::find($key);
                    foreach($value as $bid) {
                        $block = dbBlock::find($bid);
                        if ($block->seminar_id != $cid) {
                            $this->errors[] = $block->seminar_id;
                            continue;
                        }
                        if ($parent->id != $block->parent_id && $block) {
                            $block->parent_id = $parent->id;
                            $block->store();
                        }
                    }
                    if($parent != null) {
                        $parent->updateChildPositions($value);
                        $changes = true;
                    }
                }
            }

            if ($chapter_list != null) {
                $ans = $courseware->updateChildPositions($chapter_list);
                $changes = true;
            }
        } else {
            if ($remote) {
                foreach((array)$chapter_list as &$chapter_id){
                    if(strpos($chapter_id, 'remote') > -1) {
                        $original_block_id = str_replace('remote_', '', $chapter_id);
                        $db_block = dbBlock::find($original_block_id);
                        $data = array('title' => $db_block->title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                        $block = $this->createAnyBlock($courseware->id, 'Chapter', $data);
                        $this->updateListKey($subchapter_list, $chapter_id, $block->id);
                        $this->updateChapterListValue($chapter_list, $chapter_id, $block->id);
                        $chapter_id = $block->id;
                    }
                }

                foreach((array)$subchapter_list as $key => &$value) {
                    $parent_id = $key;
                    foreach($value as &$subchapter_id) {
                        if(strpos($subchapter_id, 'remote') > -1) {
                            $original_block_id = str_replace('remote_', '', $subchapter_id);
                            $db_block = dbBlock::find($original_block_id);
                            $data = array('title' => $db_block->title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                            $block = $this->createAnyBlock($parent_id, 'Subchapter', $data);
                            $this->updateListKey($section_list, $subchapter_id, $block->id);
                            $this->updateListValue($subchapter_list, $subchapter_id, $block->id);
                            $subchapter_id = $block->id;
                        }
                    }
                }

                foreach((array)$section_list as $key => &$value) {
                    $parent_id = $key;
                    foreach($value as &$section_id) {
                        if(strpos($section_id, 'remote') > -1) {
                            $remote_block_id = str_replace('remote_', '', $section_id);
                            $remote_db_block = dbBlock::find($remote_block_id);
                            $data = array('title' => $remote_db_block->title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                            $new_block = $this->createAnyBlock($parent_id, 'Section', $data);
                            $remote_ui_block = $this->plugin->getBlockFactory()->makeBlock($remote_db_block);
                            $new_ui_block = $this->plugin->getBlockFactory()->makeBlock($new_block);
                            if (in_array($remote_ui_block->icon, $new_ui_block->allowed_icons())) {
                                $new_ui_block->icon = $remote_ui_block->icon;
                            }
                            $new_ui_block->save();
                            $this->updateListKey($block_list, $section_id, intval($new_block->id));
                            $this->updateListValue($section_list, $section_id, intval($new_block->id));
                            $section_id = intval($new_block->id);
                        }
                    }
                }

                //create import folder
                $import_folder = $this->createImportFolder($cid);

                foreach((array)$block_list as $key => &$value) {
                    $parent_id = $key;
                    foreach($value as &$block_id) {
                        if(strpos($block_id, 'remote') > -1) {
                            $remote_block_id = str_replace('remote_', '', $block_id);
                            $remote_db_block = dbBlock::find($remote_block_id);
                            $remote_ui_block = $this->plugin->getBlockFactory()->makeBlock($remote_db_block);

                            $data = array('title' => $remote_db_block->title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                            $new_block = $this->createAnyBlock($parent_id, $remote_db_block->type, $data, $remote_db_block->sub_type);
                            $this->updateBlockId($block_list, $block_id, $new_block->id);
                            $block_id = intval($new_block->id);

                            $new_ui_block = $this->plugin->getBlockFactory()->makeBlock($new_block);
                            if (gettype($new_ui_block) != 'object') {
                                $new_block->delete();
                                unset($block_list[$block_id]);
                                $this->errors[] = _cw('Daten wurden nicht importiert');
                                $this->successes[] = _cw('Änderungen wurden gespeichert');

                                break 2;
                            }
                            $files = $remote_ui_block->getFiles();
                            foreach($files as &$file) {
                                $remote_file = FileRef::find($file['id']);

                                if ($remote_file != null) {
                                    $file = FileManager::copyFile($remote_file->getFiletype(), $import_folder, \User::findCurrent());

                                }
                            }

                            if ($remote_ui_block->exportProperties() != null) {
                                $new_ui_block->importProperties($remote_ui_block->exportProperties());
                            }
                            $new_ui_block->importContents($remote_ui_block->exportContents(), $files);
                        }
                    }
                }

                //remove import folder if it is empty
                if(empty($import_folder->getFiles()) && empty($import_folder->getSubfolders())) {
                    $import_folder->delete();
                }
            } else { // import
                if ($import_xml == '') {
                    $this->errors[] = _cw('Das Import-Archiv enthält keine data.xml');
                }

                $xml = DOMDocument::loadXML($import_xml);
                if (!$this->validateUploadFile($import_xml)){
                    return false;
                }
                $file_data = $decoded_request['fileData'];
                $zip_file = base64_decode(explode('base64,', $file_data['file'])[1]);

                // $tempDir = $this->extractArchive($_FILES['cw-file-upload-import']['tmp_name']);
                $tempDirZip = $GLOBALS['TMP_PATH'].'/'.uniqid();
                mkdir($tempDirZip);
                file_put_contents($tempDirZip.'/'.$file_data['name'], $zip_file);
                $tempDirFiles = $this->extractArchive($tempDirZip.'/'.$file_data['name']);
                $this->deleteRecursively($tempDirZip);
                if (!$tempDirFiles) {
                    $this->errors[] = _cw('Das Import-Archiv enthält keine Dateien');
                    return false;
                }

                $import_folder = $this->createImportFolder($cid);

                // store files
                $files = array();
                $coursewareNode = $xml->documentElement;
                foreach ($coursewareNode->childNodes as $child) {
                    if ($child instanceof DOMElement) {
                        if  ($child->tagName === 'file') {
                            $this->processFile($child, $tempDirFiles, $files, $import_folder);
                        }
                    }
                }
                // clean up temp directory
                $this->deleteRecursively($tempDirFiles);

                foreach((array)$chapter_list as &$chapter_id){
                    if(strpos($chapter_id, 'import') > -1) {
                        $chapter_tempid = str_replace('import_', '', $chapter_id);
                        $chapter_title = '';
                        foreach($xml->getElementsByTagName('chapter') as $xml_chapter) {
                            if ($xml_chapter->getAttribute('temp-id') == $chapter_tempid) {
                                $chapter_title = $xml_chapter->getAttribute('title');
                            }
                        }
                        $data = array('title' => $chapter_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                        $block = $this->createAnyBlock($courseware->id, 'Chapter', $data);
                        $this->updateListKey($subchapter_list, $chapter_id, $block->id);
                        $this->updateChapterListValue($chapter_list, $chapter_id, $block->id);
                        $chapter_id = $block->id;
                    }
                }

                foreach((array)$subchapter_list as $key => &$value) {
                    $parent_id = $key;
                    foreach($value as &$subchapter_id) {
                        if(strpos($subchapter_id, 'import') > -1) {
                            $subchapter_tempid = str_replace('import_', '', $subchapter_id);
                            $subchapter_title = '';
                            foreach($xml->getElementsByTagName('subchapter') as $xml_subchapter) {
                                if ($xml_subchapter->getAttribute('temp-id') == $subchapter_tempid) {
                                    $subchapter_title = $xml_subchapter->getAttribute('title');
                                }
                            }
                            $data = array('title' => $subchapter_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                            $block = $this->createAnyBlock($parent_id, 'Subchapter', $data);
                            $this->updateListKey($section_list, $subchapter_id, $block->id);
                            $this->updateListValue($subchapter_list, $subchapter_id, $block->id);
                            $subchapter_id = $block->id;
                        }
                    }
                }

                foreach((array)$section_list as $key => &$value) {
                    $parent_id = $key;
                    foreach($value as &$section_id) {
                        if(strpos($section_id, 'import') > -1) {
                            $section_tempid = str_replace('import_', '', $section_id);
                            $section_title = '';
                            foreach($xml->getElementsByTagName('section') as $xml_section) {
                                if ($xml_section->getAttribute('temp-id') == $section_tempid) {
                                    $section_title = $xml_section->getAttribute('title');
                                    $icon = $xml_section->getAttribute('icon');
                                }
                            }
                            $data = array('title' => $section_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                            $block = $this->createAnyBlock($parent_id, 'Section', $data);
                            $uiSection = $this->plugin->getBlockFactory()->makeBlock($block);
                            if (in_array($icon, $uiSection->allowed_icons())) {
                                $uiSection->icon = $icon;
                            }
                            $uiSection->save();
                            $this->updateListKey($block_list, $section_id, $block->id);
                            $this->updateListValue($section_list, $section_id, $block->id);
                            $section_id = $block->id;
                        }
                    }
                }

                $used_files = array();
                foreach((array)$block_list as $key => &$value) {
                    $parent_id = $key;
                    foreach($value as &$block_id) {
                        if(strpos($block_id, 'import') > -1) {
                            $block_uuid = str_replace('import_', '', $block_id);
                            $block_title = '';
                            $block_node = '';
                            $block_type = '';
                            foreach($xml->getElementsByTagName('block') as $xml_block) {
                                if ($xml_block->getAttribute('uuid') == $block_uuid) {
                                    $block_title = $xml_block->getAttribute('title');
                                    $block_type = $xml_block->getAttribute('type');
                                    $block_node = $xml_block;
                                }
                            }

                            $data = array('title' => $block_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                            $block = $this->createAnyBlock($parent_id, $block_type, $data);
                            $this->updateBlockId($block_list, $block_id, $block->id);
                            $block_id = $block->id;
                            $uiBlock = $this->plugin->getBlockFactory()->makeBlock($block);
                            if (gettype($uiBlock) != 'object') {
                                $block->delete();
                                unset($block_list[$block_id]);
                                $this->errors[] = _cw('Daten wurden nicht importiert');
                                $this->successes[] = _cw('Änderungen wurden gespeichert');

                                return;
                            }

                            $properties = array();
                            foreach ($block_node->attributes as $attribute) {

                                if (!$attribute instanceof DOMAttr) {
                                    continue;
                                }

                                if ($attribute->namespaceURI !== null) {
                                    $properties[$attribute->name] = $attribute->value;
                                }
                            }

                            if (count($properties) > 0) {
                                $uiBlock->importProperties($properties);
                            }
                            if (is_array($uiBlock->importContents(trim($block_node->textContent), $files))) {
                                $used_files = array_merge($used_files, $uiBlock->importContents(trim($block_node->textContent), $files));
                            }
                        }
                    }
                }
                 //delete unused files
                foreach($files as $file) {
                    if (!in_array($file->id , $used_files)) {
                        $import_folder->deleteFile($file->id);
                    }
                }

                if(empty($import_folder->getFiles()) && empty($import_folder->getSubfolders())) {
                    $import_folder->delete();
                }

            }

            if ($chapter_list != null) {
                $courseware->updateChildPositions($chapter_list);
                $changes = true;
            }

            foreach(array($subchapter_list, $section_list, $block_list) as $list) {
                foreach((array)$list as $key => $value) {
                    $parent = dbBlock::find($key);
                    if ($parent) {
                        $parent->updateChildPositions($value);
                    }
                }
            }

        }
        $grouped = $this->getGrouped($cid);
        $courseware = current($grouped['']);
        $this->buildTree($grouped, $courseware);
        $courseware = json_encode($courseware);

        $this->response->add_header('Content-Type', 'application/json');
        $answer = json_encode(['errors' => $this->errors, 'successes' => $this->successes, 'courseware' => $courseware]);
        $this->render_text($answer);

    }

    private function createAnyBlock($parent, $type, $data, $sub_type = '')
    {
        $block = new dbBlock();
        $parent_id = is_object($parent) ? $parent->id : $parent;
        $block->setData(array(
            'seminar_id' => $data['cid'],
            'parent_id' => $parent_id,
            'type' => $type,
            'sub_type' => $sub_type,
            'title' => $data['title'],
            'publication_date' => $data['publication_date'],
            'withdraw_date' => $data['withdraw_date'],
            'position' => $block->getNewPosition($parent_id),
            'approval' => ($parent->approval == null) ? '' : $parent->approval
        ));

        $block->store();

        return $block;
    }

    private function updateListKey(&$list, $oldkey, $newkey)
    {
        foreach($list as $key => $value) {
            if ($key == $oldkey) {
                $list[$newkey] = $list[$oldkey];
                unset($list[$oldkey]);

                return true;
            }
        }

        return false;
    }

    private function updateListValue(&$list, $old_id, $new_id)
    {
        reset($list);
        foreach($list as $el => $arr) {
            foreach($list[$el] as $key => $value) {
                if ($value == $old_id) {
                    $list[$el][$key] = $new_id;
                    return true;
                }
            }
        }
        $this->errors[] = _cw('Sortierung ist fehlgeschlagen');

        return false;
    }
    private function updateChapterListValue(&$list, $old_id, $new_id)
    {
        foreach($list as $key => $value) {
            if ($value == $old_id) {
                $list[$key] = $new_id;
                return true;
            }
        }
        $this->errors[] = _cw('Kapitelsortierung ist fehlgeschlagen');

        return false;
    }

    private function updateBlockId(&$block_list, $old_id, $new_id){
        foreach($block_list as &$list){
            foreach($list as &$block_id) {
                if ($block_id == $old_id){
                    $block_id = $new_id;
                    return true;
                }
            }
        }
        $this->errors[] = _cw('Block Id konnte nicht aktuallisiert werden');

        return false;
    }

    private function deleteRecursively($path)
    {
        if (is_dir($path)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                /** @var SplFileInfo $file */
                if (in_array($file->getBasename(), array('.', '..'))) {
                    continue;
                }

                if ($file->isFile() || $file->isLink()) {
                    unlink($file->getRealPath());
                } else if ($file->isDir()) {
                    rmdir($file->getRealPath());
                }
            }

            rmdir($path);
        } else if (is_file($path) || is_link($path)) {
            unlink($path);
        }
    }

    private function processFile(DOMElement $node, $path, &$files, $folder)
    {
        /** @var \Seminar_User $user */
        global $user;
        $originId = $node->getAttribute('id');
        $filename = $node->getAttribute('filename');
        $filesize = $node->getAttribute('filesize');

        if (!($originId && $filename && $filesize)) {

            return;
        }

        // is this file already stored
        $stored_file = FileRef::findOneBySQL('name = ? AND folder_id = ?', array($node->getAttribute('name'), $folder->id));
        if(!$stored_file) {
            $file = [
                        'name'     => $filename,
                        'type'     => mime_content_type($path.'/'.$originId.'/'.$filename),
                        'tmp_name' => $path.'/'.$originId.'/'.$filename,
                        'url'      => $node->getAttribute('url'),
                        'size'     => $node->getAttribute('filesize'),
                        'user_id'  => $user->id,
                        'error'    => ""
                    ];
            
            $standard_file = \StandardFile::create($file);
            $new_reference = $folder->addFile($standard_file);

        } else {
            $new_reference = $stored_file;
        }

        $files[$originId] = $new_reference;
    }

    private function getI18NBlockName($name) {
        $lang = getUserLanguage($this->container['current_user']->id);
        $i18n_en = array(
            'Audio' => 'Audio',
            'Audio Galerie' => 'Audio Gallery',
            'Bestätigung' => 'Confirmation',
            'Bildvergleich' => 'Image Comparison',
            'Dateiordner' => 'Folder',
            'Diagramm' => 'Chart',
            'Download' => 'Download',
            'Embed' => 'Embed',
            'Forum' => 'Forum',
            'Freitext' => 'Free Text',
            'Galerie' => 'Gallery',
            'Gruppieren' => 'Group',
            'Interactive Video' => 'Interactive Video',
            'Kommentare & Diskussion' => 'Comments & Discussion',
            'Leinwand' => 'Canvas',
            'Lernkarten' => 'Dialog Cards',
            'Link' => 'Link',
            'Merksatz' => 'Key Point',
            'Opencast' => 'Opencast',
            'PDF mit Vorschau' => 'PDF preview',
            'Quellcode' => 'Source Code',
            'Quiz' => 'Quiz',
            'Schreibmaschine' => 'Typewriter',
            'Suche' => 'Search',
            'Termin' => 'Date',
            'Verweissensitive Grafik' => 'Image Map',
            'Video' => 'Video',
            'externer Inhalt (iframe)' => 'External Content (iframe)'


        );
        if ($lang == 'de_DE') {
            return $name;
        }
        if ($lang == 'en_GB') {
            return $i18n_en[$name];
        }

        return $name;
    }
}
