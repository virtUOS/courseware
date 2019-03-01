<?php

use Mooc\DB\Block;
use Mooc\Import\XmlImport;
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

        if (!$this->container['current_user']->canCreate($this->container['current_courseware'])) {
            throw new Trails_Exception(401);
        }
        if (Navigation::hasItem('/course/mooc_courseware/block_manager')) {
            Navigation::activateItem('/course/mooc_courseware/block_manager');
        }
        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css');
        PageLayout::addScript($this->plugin->getPluginURL().'/assets/js/block_manager.js');
        PageLayout::addScript($this->plugin->getPluginURL().'/assets/js/ziploader/zip-loader.min.js');
    }

    public function index_action()
    {
        $this->errors = [];
        $this->warnings = [];
        $this->successes = [];

        if (Request::method() == 'POST' && Request::option('subcmd')=='fullimport') {
            $this->full_import();
        }

        if (Request::method() == 'POST' && Request::option('subcmd')=='store_changes') {
            $this->store_changes();
        }

        $this->cid = Request::get('cid');
        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId())),
            function($memo, $item) {
                $arr = $item->toArray();
                if (!$item->isStructuralBlock()) {
                    $arr['isBlock'] = true;
                    $arr['ui_block'] = $this->plugin->getBlockFactory()->makeBlock($item);
                }
                if ($arr['publication_date'] != null) {
                    $arr['publication_date'] = date('d.m.Y',$arr['publication_date']);
                }
                if ($arr['withdraw_date'] != null) {
                    $arr['withdraw_date'] = date('d.m.Y',$arr['withdraw_date']);
                }
                $arr['isPublished'] = $item->isPublished();
                $memo[$item->parent_id][] = $arr;
                return $memo;
            },
            array());
            
        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $this->courseware);
    }

    private function buildTree($grouped, &$root)
    {
        $this->addChildren($grouped, $root);
        if ($root['type'] !== 'Section') {
            foreach($root['children'] as &$child) {
                $this->buildTree($grouped, $child);
            }
        } else {
            $root['children'] = $this->addChildren($grouped, $root);
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = $grouped[$parent['id']];
        usort($parent['children'], function($a, $b) {
            return $a['position'] - $b['position'];
        });

        return $parent['children'];
    }

    public function full_import()
    {
        $cid = Request::get('cid');
        $filename = $_FILES['cw-file-upload-import']['tmp_name'];
        // create a temporary directory
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);
        $extracted = Studip\ZipArchive::extractToPath($filename, $tempDir);
        if (!$extracted) {
            $this->errors[] = _cw('Das Import-Archiv ist beschädigt.');

            return $this->redirect('block_manager?cid='.$cid.'&errors=true');
        }

        $xml_file = $tempDir.'/data.xml';
        if (!is_file($xml_file)) {
            $this->errors[] = _cw('Import-Archiv enthält keine Datendatei data.xml.');

            return $this->redirect('block_manager?cid='.$cid.'&errors=true');
        }
        $xml = file_get_contents($xml_file);

        if ($this->validateUploadFile($xml)) {
            $import_folder = $this->createImportFolder();
            $courseware = $this->container['current_courseware'];
            $importer = new XmlImport($this->plugin->getBlockFactory());
            try {
                $importer->import($tempDir, $courseware, $import_folder);
            } catch (Exception $e){
                $this->errors[] = $e;
            }
            if ((count($this->errors) == 0) &&(count($this->warnings) == 0)) {
                $this->successes[] = _cw('success on full import');
            }
        }

        $this->deleteRecursively($tempDir);
    }

    private function createImportFolder()
    {
        $root_folder = Folder::findTopFolder($GLOBALS['SessionSeminar']);
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
            if (!empty($this->errors)){
                array_unshift($errors, _cw('Die Datendatei data.xml enthält kein valides XML.'));

                return false;
            }
        }

        return true;
    }

    public function store_changes()
    {
        $cid = Request::get('cid');
        $import = Request::get('import');
        $import_xml = Request::get('importXML');
        $chapter_list = json_decode(Request::get('chapterList'), true);
        $subchapter_list = json_decode(Request::get('subchapterList'), true);
        $section_list = json_decode(Request::get('sectionList'), true);
        $block_list = json_decode(Request::get('blockList'), true);
        $courseware = $this->container['current_courseware'];

        if ($import == 'false') {
            foreach(array($subchapter_list, $section_list, $block_list) as $list) {
                foreach($list as $key => $value) {
                    $parent = \Mooc\DB\Block::find($key);
                    foreach($value as $bid) {
                        $block = \Mooc\DB\Block::find($bid);
                        if ($parent->id != $block->parent_id) {
                            $block->parent_id = $parent->id;
                            $block->store();
                        }
                    }
                    $parent->updateChildPositions($value);
                }
            }
    
            $courseware = \Mooc\DB\Block::findCourseware($cid);
            if ($chapter_list != null) {
                $courseware->updateChildPositions($chapter_list);
            }
            $this->successes[] = _cw('Änderungen wurden gespeichert.');

            return true;
        } else {
            if ($import_xml == '') {
                $this->errors[] = _cw('Das Import-Archiv enthält keine data.xml.');
            }

            $xml = DOMDocument::loadXML($import_xml);
            if (!$this->validateUploadFile($import_xml)){
                return false;
            }

            // load files into temp folder
            $upload_file = $_FILES['cw-file-upload-import']['tmp_name'];
            $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
            mkdir($tempDir);
            $extracted = Studip\ZipArchive::extractToPath($upload_file, $tempDir);
            if (!$extracted) {
                $this->errors[] = _cw('Das Import-Archiv ist beschädigt.');
                return false;
            }

            $import_folder = $this->createImportFolder();

            // store files
            $files = array();
            $coursewareNode = $xml->documentElement;
            foreach ($coursewareNode->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    if  ($child->tagName === 'file') {
                        $this->processFile($child, $tempDir, $files, $import_folder);
                    }
                }
            }
            // clean up temp directory
            $this->deleteRecursively($tempDir);

            // get relevant Blocks from Lists
            // find them in XML
            // create Blocks and change ids in Lists
            // update positions

            foreach($chapter_list as &$chapter_id){
                if(strpos($chapter_id, 'import') > -1) {
                    $chapter_tempid = str_replace('import-', '', $chapter_id);
                    $chapter_title = '';
                    foreach($xml->getElementsByTagName('chapter') as $xml_chapter) {
                        if ($xml_chapter->getAttribute('temp-id') == $chapter_tempid) {
                            $chapter_title = $xml_chapter->getAttribute('title');
                        }
                    }
                    $data = array('title' => $chapter_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                    $block = $this->createAnyBlock($courseware->id, 'Chapter', $data);
                    $this->updateListKey($subchapter_list, $chapter_id, $block->id);
                    $chapter_id = $block->id;
                }
            }

            foreach($subchapter_list as $key => &$value) {
                $parent_id = $key;
                foreach($value as &$subchapter_id) {
                    if(strpos($subchapter_id, 'import') > -1) {
                        $subchapter_tempid = str_replace('import-', '', $subchapter_id);
                        $subchapter_title = '';
                        foreach($xml->getElementsByTagName('subchapter') as $xml_subchapter) {
                            if ($xml_subchapter->getAttribute('temp-id') == $subchapter_tempid) {
                                $subchapter_title = $xml_subchapter->getAttribute('title');
                            }
                        }
                        $data = array('title' => $subchapter_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                        $block = $this->createAnyBlock($parent_id, 'Subchapter', $data);
                        $this->updateListKey($section_list, $subchapter_id, $block->id);
                        $subchapter_id = $block->id;
                    }
                }
            }

            foreach($section_list as $key => &$value) {
                $parent_id = $key;
                foreach($value as &$section_id) {
                    if(strpos($section_id, 'import') > -1) {
                        $section_tempid = str_replace('import-', '', $section_id);
                        $section_title = '';
                        foreach($xml->getElementsByTagName('section') as $xml_section) {
                            if ($xml_section->getAttribute('temp-id') == $section_tempid) {
                                $section_title = $xml_section->getAttribute('title');
                            }
                        }
                        $data = array('title' => $section_title, 'cid' => $cid, 'publication_date' => null, 'withdraw_date' => null);
                        $block = $this->createAnyBlock($parent_id, 'Section', $data);
                        $icon = $xml_section->getAttribute('icon');
                        $uiSection = $this->plugin->getBlockFactory()->makeBlock($block);
                        if (in_array($icon, $uiSection->allowed_icons())) {
                            $uiSection->icon = $icon;
                        }
                        $uiSection->save();
                        $this->updateListKey($block_list, $section_id, $block->id);
                        $section_id = $block->id;
                    }
                }
            }

            $used_files = array();
            foreach($block_list as $key => &$value) {
                $parent_id = $key;
                foreach($value as &$block_id) {
                    if(strpos($block_id, 'import') > -1) {
                        $block_uuid = str_replace('import-', '', $block_id);
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
                        $this->updateListKey($block_list, $block_id, $block->id);
                        $block_id = $block->id;
                        $uiBlock = $this->plugin->getBlockFactory()->makeBlock($block);
                        if (gettype($uiBlock) != 'object') { 
                            $block->delete();
                            unset($block_list[$block_id]);
                            $this->errors[] = _cw('Daten wurden nicht importiert.');
                            $this->successes[] = _cw('Änderungen wurden gespeichert.');

                            return;
                        }

                        $properties = array();
                        foreach ($block_node->attributes as $attribute) {
                           
                            if (!$attribute instanceof DOMAttr) {
                                continue;
                            }
                            echo $attribute->name." : ".$attribute->value;
                            if ($attribute->namespaceURI !== null) {
                                $properties[$attribute->name] = $attribute->value;
                            }
                        }
                        if (count($properties) > 0) {
                            $uiBlock->importProperties($properties);
                        }

                        $used_files = array_merge($used_files, $uiBlock->importContents(trim($block_node->textContent), $files));
                    }
                }
            }

            // delete unused files
            foreach($files as $file) {
                if (!in_array($file->id , $used_files)) {
                    $import_folder->deleteFile($file->id);
                }
            }

            if(empty($import_folder->getFiles())) {
                $import_folder->delete();
            }

            $this->successes[] = _cw('Änderungen wurden gespeichert.');
            $this->successes[] = _cw('Daten wurden erfolgreich importiert');

            return true;
        }
    }

    private function createAnyBlock($parent, $type, $data)
    {
        $block = new \Mooc\DB\Block();
        $parent_id = is_object($parent) ? $parent->id : $parent;
        $block->setData(array(
            'seminar_id' => $data['cid'],
            'parent_id' => $parent_id,
            'type' => $type,
            'title' => $data['title'],
            'publication_date' => $data['publication_date'],
            'withdraw_date' => $data['withdraw_date'],
            'position' => $block->getNewPosition($parent_id)
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
            $new_reference = $folder->createFile($file);

        } else {
            $new_reference = $stored_file;
        }

        $files[$originId] = $new_reference;
    }

}
