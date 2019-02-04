<?php

namespace Mooc\UI\CanvasBlock;

use Mooc\UI\Block;

class CanvasBlock extends Block
{
    const NAME = 'Canvas';
    const BLOCK_CLASS = 'interaction';
    const DESCRIPTION = 'Draw something awesome';

    public function initialize()
    {
        $this->defineField('canvas_content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('canvas_draw', \Mooc\SCOPE_USER, '');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $content = json_decode($this->canvas_content);
        $bg_image = $content->image ? 1 : 0;
        if ($content->source == "cw") {
            $file = \FileRef::find($content->image_id);
            if ($file) {
                $image_url = $file->getDownloadURL();
                $access = ($file->terms_of_use->download_condition == 0) ? true : false;
            }
        } else {
            $image_url = $content->image_url;
            $access = true;
        }

        return array_merge(
            $this->getAttrArray(),
            array('image_url'=> $image_url, 'bg_image' => $bg_image)
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

        return array_merge(
            $this->getAttrArray(), 
            array(
                'image_url'=> $content->image_url, 
                'image_files_user' => $files_arr['userfilesarray'], 
                'image_files_course' => $files_arr['coursefilesarray'], 
                'no_image_files' => $no_files, 
                'other_user_file' => $other_user_file
            )
        );
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
        }

        return;
    }

    private function showFiles($file_id)
    {
        $coursefilesarray = array();
        $userfilesarray = array();
        $course_folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        $user_folders =  \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $image_id_found = false;

        foreach ($course_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink())) {
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

    public function getFiles()
    {
        $content = json_decode($this->canvas_content);

        if ($content->source != 'cw') {
            return;
        }

        if ($content->image_id == '') {
            return;
        }
        $file_ref = new \FileRef($content->image_id);
        $file = new \File($file_ref->file_id);
        
        $files[] = array(
            'id' => $content->image_id,
            'name' => $file_ref->name,
            'description' => $file_ref->description,
            'filename' => $file->name,
            'filesize' => $file->size,
            'url' => $file->getURL(),
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
            }
        }
    }
}
