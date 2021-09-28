<?php

namespace Mooc\UI\CanvasBlock;

use Mooc\UI\Block;
use Mooc\DB\Field as DBField;

class CanvasBlock extends Block
{
    const NAME = 'Leinwand';
    const BLOCK_CLASS = 'interaction';
    const DESCRIPTION = 'Zeichnen und Schreiben auf einem Bild';

    public function initialize()
    {
        $this->defineField('canvas_content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('canvas_draw', \Mooc\SCOPE_USER, '');
    }

    public function student_view()
    {
        global $user;

        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $content = json_decode($this->canvas_content);
        $bg_image = $content->image ? 1 : 0;
        if ($content->source == "cw") {
            $file_ref = \FileRef::find($content->image_id);
            if ($file_ref) {
                $image_url =$this->getFileURL($file_ref);
                $access = $this->isFileDownloadable($file_ref);
            }
        } else {
            $image_url = $content->image_url;
            $access = true;
        }

        $fields = DBField::findBySQL('block_id = ? AND name = ? AND NOT user_id = ?', array($this->id, 'canvas_draw', $user->id));
        foreach($fields as $field){
            $draws [] = $field->json_data;
        }

        return array_merge(
            $this->getAttrArray(),
            array(
                'image_url'=> $image_url,
                'bg_image' => $bg_image,
                'description' => $content->description,
                'upload_enabled' => $content->upload_enabled,
                'upload_folder' => $content->upload_folder_name,
                'draws' => json_encode($draws),
                'show_userdata' => $this->showUserdata($content->show_userdata)
            )
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $content = json_decode($this->canvas_content);

        $files_arr = $this->showFiles($content->image_id);

        $no_files = empty($files_arr['userfilesarray']) && empty($files_arr['coursefilesarray']) && ($files_arr['image_id_found'] == false) && empty($content->image_id);
        
        if((!$files_arr['image_id_found']) && (!empty($content->image_id))){
            $other_user_file = array('id' => $content->image_id, 'name' => $content->image_name);
        } else {
            $other_user_file = false;
        }

        $folders =  \Folder::findBySQL('range_id = ? AND folder_type != ?', array($this->container['cid'], 'RootFolder'));
        $root_folder = \Folder::findOneBySQL('range_id = ? AND folder_type = ?', array($this->container['cid'], 'RootFolder'));
        $root_folder->name = 'Hauptordner';
        array_unshift($folders, $root_folder);

        return array_merge(
            $this->getAttrArray(), 
            array(
                'image_url'=> $content->image_url, 
                'image_files_user' => $files_arr['userfilesarray'], 
                'image_files_course' => $files_arr['coursefilesarray'], 
                'no_image_files' => $no_files, 
                'other_user_file' => $other_user_file,
                'folders' => $folders
            )
        );
    }

    public function preview_view()
    {
        $content = json_decode($this->canvas_content);
        if ($content->source == "cw") {
            $file_ref = \FileRef::find($content->image_id);
            if ($file_ref) {
                $image_url = $this->getFileURL($file_ref);
                $access = $this->isFileDownloadable($file_ref);
            }
        } else {
            $image_url = $content->image_url;
            $access = true;
        }

        return array('bg_img' => $image_url);
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset($data['canvas_content'])) {
            $this->canvas_content = $data['canvas_content'];
        }

        return;
    }

    public function store_draw_handler(array $data)
    {
        if (isset($data['canvas_draw'])) {
            $this->canvas_draw = $data['canvas_draw'];
            $this->setGrade(1.0);
        }

        return;
    }

    public function store_image_handler(array $data)
    {
        global $user;

        if (empty($data['image'])) {
            return false;
        }

        $content = json_decode($this->canvas_content);
        $upload_folder = \FileManager::getTypedFolder($content->upload_folder_id);

        $file_name = $user->nachname.'_'.$user->vorname.'_'.date('d-m-Y').'.jpeg';
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);
        file_put_contents($tempDir.'/'.$file_name, base64_decode(explode('base64,', $data['image'])[1]));

        $file = [
            'name'     => $file_name,
            'type'     => mime_content_type($tempDir.'/'.$file_name),
            'tmp_name' => $tempDir.'/'.$file_name,
            'size'     => filesize($tempDir.'/'.$file_name),
            'user_id'  => $user->id,
            'content_terms_of_use_id' => 'SELFMADE_NONPUB',
            'error'    => ""
        ];

        $standard_file = \StandardFile::create($file);
        $new_reference = $upload_folder->addFile($standard_file);
        $this->deleteRecursively($tempDir);

        return $new_reference;
    }

    private function showUserdata($show_userdata)
    {
        switch($show_userdata){
            case 'off':
                return false;
            case 'teacher':
                return $this->container['current_user']->canUpdate($this);
            case 'all':
                return true;
            default:
                return false;
        }
    }

    private function showFiles($file_id)
    {
        $coursefilesarray = array();
        $userfilesarray = array();
        $course_folders = \Folder::findBySQL('range_id = ? AND folder_type NOT IN (?)', array($this->container['cid'], array('HiddenFolder', 'HomeworkFolder')));
        $hidden_folders = $this->getHiddenFolders();
        $course_folders = array_merge($course_folders, $hidden_folders);
        $user_folders = \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $image_id_found = false;

        foreach ($course_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink()) && (strpos($ref->mime_type, 'svg') === false)) {
                    $coursefilesarray[] = $ref;
                }
                if($ref->id == $file_id) {
                    $image_id_found = true;
                }
            }
        }

        foreach ($user_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink())) {
                    $userfilesarray[] = $ref;
                }
                if($ref->id == $file_id) {
                    $image_id_found = true;
                }
            }
        }

        return array('coursefilesarray' => $coursefilesarray, 'userfilesarray' => $userfilesarray, 'image_id_found' => $image_id_found);
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

    private function getAttrArray()
    {
        return array(
            'canvas_content' => $this->canvas_content,
            'canvas_draw' => $this->canvas_draw
        );
    }

    public function exportProperties()
    {
       return array('canvas_content' => $this->canvas_content);
    }

    public function getHtmlExportData()
    {
        $content = json_decode($this->canvas_content);
        if ($content->source == 'cw') {
            $content->url = './' . $content->image_id . '/' . $content->image_name;
        }

        return  $content;
    }

    public function getFiles()
    {
        $files = array();
        $content = json_decode($this->canvas_content);

        if ($content->source != 'cw') {
            return $files;
        }

        if ($content->image_id == '') {
            return $files;
        }
        $file_ref = new \FileRef($content->image_id);
        $file = new \File($file_ref->file_id);

        $files[] = array(
            'id' => $content->image_id,
            'name' => $file_ref->name,
            'description' => $file_ref->description,
            'filename' => $file->name,
            'filesize' => $file->size,
            'url' => $this->isFileAnURL($file_ref),
            'path' => $file->getPath()
        );

        return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/canvas/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/canvas/canvas-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['canvas_content'])) {
            $this->canvas_content = $properties['canvas_content'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        $content = json_decode($this->canvas_content);

        if ($content->source != 'cw') {
            return;
        }

        foreach($files as $file){
            if ($file->name == '') {
                continue;
            }
            if($content->image_name == $file->name) {
                $content->image_id = $file->id;
                $this->save();

                return array($file->id);
            }
        }
    }
}
