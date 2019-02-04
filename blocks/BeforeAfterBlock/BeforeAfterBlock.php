<?php
namespace Mooc\UI\BeforeAfterBlock;

use Mooc\UI\Block;

class BeforeAfterBlock extends Block 
{
    const NAME = 'Bildvergleich';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Vergleicht zwei Bilder mit einem Schieberegler';

    public function initialize()
    {
        $this->defineField('ba_before', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('ba_after', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $this->setGrade(1.0);
        $ba_img_before = json_decode($this->ba_before)->url;
        $ba_img_after = json_decode($this->ba_after)->url;
        $ba_enable = (($ba_img_before != '') && ($ba_img_after != '')) ? true : false;

        return array(
            'beforeafter_img_before' => $ba_img_before,
            'beforeafter_img_after' => $ba_img_after,
            'ba_enable' => $ba_enable
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $before = json_decode($this->ba_before);
        $after = json_decode($this->ba_after);
        $ba_img_before_external = json_decode($this->ba_before)->source == 'url' ? true : false;
        $ba_img_after_external = json_decode($this->ba_after)->source == 'url' ? true : false;

        $files_arr = $this->showFiles($before->file_id, $after->file_id);

        $no_files = 
            empty($files_arr['userfilesarray']) && 
            empty($files_arr['coursefilesarray']) && 
            ($files_arr['before_file_id_found'] == false) && 
            ($files_arr['after_file_id_found'] == false) &&
            empty($before->file_id) &&
            empty($after->file_id);

        if((!$files_arr['before_file_id_found']) && (!empty($before->file_id)) ){
            $before_other_user_file = array('id' => $before->file_id, 'name' => $before->file_name, 'download_url' => $before->url);
        } else {
            $before_other_user_file = false;
        }

        if((!$files_arr['after_file_id_found']) && (!empty($after->file_id))) {
            $after_other_user_file = array('id' => $after->file_id, 'name' => $after->file_name, 'download_url' => $after->url);
        } else {
            $after_other_user_file = false;
        }

        return array_merge($this->getAttrArray(), array(
            'image_files_user' => $files_arr['userfilesarray'],
            'image_files_course' => $files_arr['coursefilesarray'],
            'before_other_user_file' => $before_other_user_file,
            'after_other_user_file' => $after_other_user_file,
            'no_files' => $no_files,
            'img_before' => $before->url,
            'img_after' => $after->url,
            'file_id_before' => $before->file_id,
            'file_id_after' => $after->file_id,
            'img_before_external' => $ba_img_before_external,
            'img_after_external' => $ba_img_after_external
        ));
    }

    private function getAttrArray() 
    {
        return array(
            'ba_before' => $this->ba_before,
            'ba_after' => $this->ba_after
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['ba_before'])) {
            $this->ba_before = \STUDIP\Markup::purifyHtml((string) $data['ba_before']);
        } 
        if (isset ($data['ba_after'])) {
            $this->ba_after = \STUDIP\Markup::purifyHtml((string) $data['ba_after']);
        } 

        return;
    }

    public function exportProperties()
    { 
       return array(
            'ba_before' => $this->ba_before,
            'ba_after' => $this->ba_after
            );
    }

    public function getFiles()
    {
        $ba_files = [];
        $before = json_decode($this->ba_before);
        $after = json_decode($this->ba_after);
        
        if ($before->source == 'file') {
            array_push($ba_files, $before->file_id);
        }
        if ($after->source == 'file') {
            array_push($ba_files, $after->file_id);
        }

        foreach ($ba_files as $ba_file){
            $file_ref = new \FileRef($ba_file);
            $file = new \File($file_ref->file_id);

            $files[] = array(
                'id' => $ba_file,
                'name' => $file_ref->name,
                'description' => $file_ref->description,
                'filename' => $file->name,
                'filesize' => $file->size,
                'url' => $file->getURL(),
                'path' => $file->getPath()
            );
        }

        return $files;
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/beforeafter/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/beforeafter/beforeafter-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['ba_before'])) {
            $this->ba_before = $properties['ba_before'];
        }
        if (isset($properties['ba_after'])) {
            $this->ba_after = $properties['ba_after'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        $ba_before = json_decode($this->ba_before);
        $ba_after = json_decode($this->ba_after);

        foreach($files as $file){
            if(($ba_after->file_name == $file->name) && ($ba_after->source == 'file')) {
                $ba_after->file_id = $file->id;
                $file_ref_after = new \FileRef($ba_after->file_id);
                $ba_after->url = $file_ref_after->download_url;
            }
            if (($ba_before->file_name == $file->name) && ($ba_before->source == 'file')) {
                $ba_before->file_id = $file->id;
                $file_ref_before = new \FileRef($ba_before->file_id);
                $ba_before->url = $file_ref_before->download_url;
            }
        }

        $this->ba_before = json_encode($ba_before);
        $this->ba_after = json_encode($ba_after);

        $this->save();
    }

    private function showFiles($before_file_id, $after_file_id)
    {
        $coursefilesarray = array();
        $userfilesarray = array();
        $course_folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        $user_folders =  \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $before_file_id_found = false;
        $after_file_id_found = false;

        foreach ($course_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink())) {
                    $coursefilesarray[] = $ref;
                }
                if($ref->id == $before_file_id){
                    $before_file_id_found = true;
                }
                if($ref->id == $after_file_id){
                    $after_file_id_found = true;
                }
            }
        }

        foreach ($user_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink())) {
                    $userfilesarray[] = $ref;
                }
                if($ref->id == $before_file_id){
                    $before_file_id_found = true;
                }
                if($ref->id == $after_file_id){
                    $after_file_id_found = true;
                }
            }
        }

        return array(
            'coursefilesarray' => $coursefilesarray,
            'userfilesarray' => $userfilesarray,
            'before_file_id_found' => $before_file_id_found,
            'after_file_id_found' => $after_file_id_found);
    }
}
