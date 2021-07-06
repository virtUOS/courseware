<?php
namespace Mooc\UI\ImageMapBlock;

use Mooc\UI\Block;
use Mooc\DB\Block as DBBlock;

class ImageMapBlock extends Block
{
    const NAME = 'Verweissensitive Grafik';
    const BLOCK_CLASS = 'interaction';
    const DESCRIPTION = 'Beliebige Bereiche auf einem Bild lassen sich verlinken';

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
        if ($content != null) {
            $file = \FileRef::find($content->image_id);
            if ($file) {
                $image_url = $this->getFileURL($file);
                $access = $this->isFileDownloadable($file);
            }

            foreach($content->shapes as $shape) {
                if ($shape->link_type == "internal") {
                    $shape->target = "courseware?cid=".$this->container['cid']."&selected=".$this->getTargetId($shape->target)['id'];
                }
            }
            $content = json_encode($content);
        } else {
            $content = '';
        }

        $this->setGrade(1.0);

        return array_merge($this->getAttrArray(), array(
            'image_url' => $image_url,
            'content' => $content
        ));
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $content = json_decode($this->image_map_content);
        $files_arr = $this->showFiles($content->image_id);

        $no_files = empty($files_arr['userfilesarray']) && empty($files_arr['coursefilesarray']) && ($files_arr['image_id_found'] == false) && empty($content->image_id);

        if ((!$files_arr['image_id_found']) && (!empty($content->image_id))) {
            $other_user_file = array('id' => $content->image_id, 'name' => $content->image_name);
        } else {
            $other_user_file = false;
        }


        $file_ref = \FileRef::find($content->image_id);
        if ($file_ref) {
           $image_url = $this->getFileURL($file_ref);
            $access = $this->isFileDownloadable($file_ref);
        }

        if (strpos($this->getModel()->parent->title, "AsideSection") > -1) {
            $hasinternal = false;
        } else {
            $hasinternal = true;
        }
        $inthischapter = $this->getThisChapterSiblings();
        $inotherchapters= $this->getOtherSubchapters();

        return array_merge($this->getAttrArray(), array(
            'image_files_user'      => $files_arr['userfilesarray'],
            'image_files_course'    => $files_arr['coursefilesarray'],
            'no_image_files'        => $no_files,
            'other_user_file'       => $other_user_file,
            'image_url'             => $image_url,
            'inthischapter'         => $inthischapter,
            'inotherchapters'       => $inotherchapters,
            'hasinternal'           => $hasinternal,
            'hasnext'               => $this->getTargetId("next")['id'] != null,
            'hasprev'               => $this->getTargetId("prev")['id'] != null
        ));
    }

    public function preview_view()
    {
        $content = json_decode($this->image_map_content);
        $file = \FileRef::find($content->image_id);
        if ($file) {
            $image_url = $this->getFileURL($file);
        }

        return array('image_url' => $image_url);
    }

    private function getAttrArray()
    {
        return array(
            'image_map_content' => $this->image_map_content
        );
    }

    private function getTargetId($target)
    {
        if (!is_string($target)) {
            return '';
        }
        $id = '';
        $type = '';
        $section = $this->getModel()->parent;
        $subchapter = $section->parent;
        $chapter = $subchapter->parent;
        $courseware = $this->container['current_courseware'];

        if ($target == 'next') {
            $id = $courseware->getNeighborSections($section)['next']['id'];
            $type = $courseware->getNeighborSections($section)['next']['type'];
        }

        if ($target == 'prev') {
            $id = $courseware->getNeighborSections($section)['prev']['id'];
            $type = $courseware->getNeighborSections($section)['prev']['type'];
        }

        if (strpos($target, 'sibling') > -1) {
            $num = (int)substr($target, 7);
            $id = $this->getModel()->parent->parent->parent->children[$num]['id'];
            $type = $this->getModel()->parent->parent->parent->children[$num]['type'];
        }

        if (strpos($target, 'other') > -1) {
            $chapter_pos = substr($target, 5);
            $chapter_pos = (int)strtok($chapter_pos,'_cpos');
            $subchapter_pos = (int)substr($target, strpos($target, '_item') + 5);

            $thischapter = $this->getModel()->parent->parent->parent;
            $allchapters = $thischapter->parent->children;
            $i = 0; $this_chapter_pos = "";
            foreach($allchapters as $chapter) {
                if($thischapter->id == $chapter->id) {
                    $this_chapter_pos = $i;
                }
                $i++;
            }

            $chatper = $allchapters[$this_chapter_pos + $chapter_pos];
            $id = $chatper->children[$subchapter_pos]['id'];
            $type = $chatper->children[$subchapter_pos]['type'];
        }

        return array('id' => $id, 'type' => $type);
    }

    private function getThisChapterSiblings()
    {
        $inthischapter = array();
        $chapter = $this->getModel()->parent->parent->parent;
        $children = $chapter->children;
        $i = 0;
        foreach ($children as $sibling) {
            array_push($inthischapter, array("value" => "sibling".$i, "title" => $sibling->title));
            $i++;
        }

        return $inthischapter;
    }

    private function getOtherSubchapters()
    {
        $inotherchapters = array();
        $thischapter = $this->getModel()->parent->parent->parent;
        $allchapters = $thischapter->parent->children;
        $i = 0; $this_chapter_pos = '';

        foreach($allchapters as $chapter) {
            if($thischapter->id == $chapter->id) {
                $this_chapter_pos = $i;
            }
            $i++;
        }

        foreach($allchapters as $key => $chapter) {
            if ($key == $this_chapter_pos) {
                continue;
            }
            $relativ_chapter_pos = $key - $this_chapter_pos;
            $subchapters = $chapter->children;
            $i = 0;
            foreach ($subchapters as $subchapter) {
                array_push($inotherchapters, array(
                    'value' => 'other_cpos'.$relativ_chapter_pos.'_item'.$i,
                    'title' => $chapter->title.' -> '.$subchapter->title
                ));
                $i++;
            }
        }

        return $inotherchapters;
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
        $course_folders = \Folder::findBySQL('range_id = ? AND folder_type NOT IN (?)', array($this->container['cid'], array('HiddenFolder', 'HomeworkFolder')));
        $hidden_folders = $this->getHiddenFolders();
        $course_folders = array_merge($course_folders, $hidden_folders);
        $user_folders = \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $image_id_found = false;

        foreach ($course_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink()) && (strpos($ref->mime_type, 'svg') === false)) {
                    $url = $this->getFileURL($ref);
                    $ref = $ref->toArray();
                    $ref['url'] = $url;
                    $coursefilesarray[] = $ref;
                }
                if($ref['id'] == $file_id) {
                    $image_id_found = true;
                }
            }
        }

        foreach ($user_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink())) {
                    $url = $this->getFileURL($ref);
                    $ref = $ref->toArray();
                    $ref['url'] = $url;
                    $userfilesarray[] = $ref;
                }
                if($ref['id'] == $file_id) {
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

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function pdfexport_view()
    {
        return array();
    }

    public function getHtmlExportData()
    {
        $content = json_decode($this->image_map_content);
        if ($content != null) {
            foreach($content->shapes as $shape) {
                if ($shape->link_type == "internal") {
                    $shape->target = $this->getTargetId($shape->target);
                }
            }
        } else {
            $content = '';
        }

        return $content;
    }

    public function getFiles()
    {
        $files = array();
        $content = json_decode($this->image_map_content);
        if($content) {
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
        }

        return $files;
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
        $content = json_decode($this->image_map_content);

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
