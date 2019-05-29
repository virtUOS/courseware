<?php
namespace Mooc\UI\ImageMapBlock;

use Mooc\UI\Block;

class ImageMapBlock extends Block 
{
    const NAME = 'ImageMap';
    const BLOCK_CLASS = 'interaction';
    const DESCRIPTION = 'ImageMap';

    public function initialize()
    {
        $this->defineField('image_map_content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        global $user;
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $content = json_decode($this->image_map_content);
        if ($content->source == "cw") {
            $file = \FileRef::find($content->image_id);
            if ($file) {
                $image_url = $file->getDownloadURL();
                $access = ($file->terms_of_use->fileIsDownloadable($file, false)) ? true : false;
            }
        } else {
            $image_url = $content->image_url;
            $access = true;
        }

        return array_merge($this->getAttrArray(), array(
            'image_url' => $image_url
        ));
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $content = json_decode($this->image_map_content);
        $files_arr = $this->showFiles($content->image_id);

        $no_files = empty($files_arr['userfilesarray']) && empty($files_arr['coursefilesarray']) && ($files_arr['image_id_found'] == false) && empty($content->image_id);
        
        if((!$files_arr['image_id_found']) && (!empty($content->image_id))){
            $other_user_file = array('id' => $content->image_id, 'name' => $content->image_name);
        } else {
            $other_user_file = false;
        }

        if ($content->source == "cw") {
            $file = \FileRef::find($content->image_id);
            if ($file) {
                $image_url = $file->getDownloadURL();
                $access = ($file->terms_of_use->fileIsDownloadable($file, false)) ? true : false;
            }
        } else {
            $image_url = $content->image_url;
            $access = true;
        }

        return array_merge($this->getAttrArray(), array(
            'image_files_user' => $files_arr['userfilesarray'], 
            'image_files_course' => $files_arr['coursefilesarray'], 
            'no_image_files' => $no_files, 
            'other_user_file' => $other_user_file,
            'image_url' => $image_url
        ));
    }

    public function preview_view()
    {

        return array();
    }

    private function getAttrArray() 
    {
        return array(
            'image_map_content' => $this->image_map_content
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['image_map_content'])) {
            $this->image_map_content = (string) $data['image_map_content'];
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

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function getFiles()
    {
        // TODO
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/image_map/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/image_map/image_map-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['image_map_content'])) {
            $this->image_map_content = $properties['image_map_content'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        // TODO
    }
}
