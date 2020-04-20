<?php
namespace Mooc\UI\DownloadBlock;

use Mooc\UI\Block;

class DownloadBlock extends Block 
{
    const NAME = 'Download';
    const BLOCK_CLASS = 'function';
    const DESCRIPTION = 'Stellt eine Datei aus dem Dateibereich zum Download bereit';

    public function initialize()
    {
        $this->defineField('file', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_name', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('folder_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_title', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_info', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_success', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_grade', \Mooc\SCOPE_BLOCK, false);
    }

    public function student_view()
    {
        $file = \FileRef::find($this->file_id);
        if ($file) { 
            $url = $file->getDownloadURL('force');
            $access = ($file->terms_of_use->fileIsDownloadable($file, false)) ? true : false;
            $icon = $this->getIcon($file);

            $file_available = true;
        } else { 
            $url = '';
            $icon = '';
            $file_available = false;
            $access = true;
        }

        if (!$this->download_grade) {
            $this->setGrade(1.0);
        }

        return array_merge(
            $this->getAttrArray(), 
            array('confirmed' => !! $this->getProgress()->grade, 
                  'url' => $url,
                  'icon' => $icon,
                  'download_access' => $access,
                  'file_available'=> $file_available,
                  'isAuthor' => $this->getUpdateAuthorization()
            )
        );
    }

    public function author_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $folder_id = $this->folder_id;
        
        $this->authorizeUpdate();
        $allfiles = $this->showFiles($folder_id);
        $folders =  \Folder::findBySQL('range_id = ? AND folder_type NOT IN (?)', array($this->container['cid'], array('RootFolder', 'HomeworkFolder', 'HiddenFolder')));
        $hidden_folders = $this->getHiddenFolders();
        $folders = array_merge($folders, $hidden_folders);
        $root_folder = \Folder::findOneBySQL('range_id = ? AND folder_type = ?', array($this->container['cid'], 'RootFolder'));
        $root_folder->name = 'Hauptordner';
        array_unshift($folders, $root_folder);
        $user_folders =  \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $other_user_folder = false;

        if(!empty($folder_id)) {
            $all_folders = array_merge($folders, $user_folders);
            $folder_found = false;
            foreach($all_folders as $folder) {
                if ($folder->id == $folder_id) {
                    $folder_found = true;
                    break;
                }
            }
            if(!$folder_found) {
                $other_user_folder[] = \Folder::find($folder_id);
            }
        }

        return array_merge($this->getAttrArray(), array('allfiles' => $allfiles, 'folders' => $folders, 'user_folders' => $user_folders, 'other_user_folder' => $other_user_folder));
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

    public function preview_view()
    {

        return array('file' => $this->file);
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset ($data['file'])) {
            $this->file = (string) $data['file'];
            $this->file_id = (string) $data['file_id'];
            $this->file_name = (string) $data['file_name'];
            $this->folder_id = (string) $data['folder_id'];
            $this->download_title = \STUDIP\Markup::purifyHtml((string) $data['download_title']);
            $this->download_info = \STUDIP\Markup::purifyHtml((string) $data['download_info']);
            $this->download_success = \STUDIP\Markup::purifyHtml((string) $data['download_success']);
            $this->download_grade = $data['download_grade'];
        }

        return;
    }

    public function setfolder_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset ($data['folder_id'])) {

            return $this->showFiles($data['folder_id']);
        }

        return false;
    }

    public function download_handler($data)
    {
        $this->setGrade(1.0);

        return ;
    }

    private function showFiles($folder_id)
    {
        $response = \FileRef::findBySQL('folder_id = ?', array($folder_id));
        $filesarray = array();
        foreach ($response as $item) {
            $filesarray[] = array("id" => $item->id, "name" => $item->name);
        }

        return $filesarray;
    }

    private function getAttrArray() 
    {
        return array(
            'file' => $this->file, 
            'file_id' => $this->file_id, 
            'file_name' => $this->file_name, 
            'folder_id' => $this->folder_id,
            'download_title' => $this->download_title,
            'download_info' => $this->download_info,
            'download_success' => $this->download_success,
            'download_grade'=> $this->download_grade
        );
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function getFiles()
    {
        $files = array();
        if ($this->file_id == '') {
            return $files;
        }
        $file_ref = new \FileRef($this->file_id);
        $file = new \File($file_ref->file_id);
        
        $files[] = array(
            'id' => $this->file_id,
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
        return 'http://moocip.de/schema/block/download/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/download/download-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['file'])) {
            $this->file = $properties['file'];
        }

        if (isset($properties['file_name'])) {
            $this->file_name = $properties['file_name'];
        }

        if (isset($properties['download_title'])) {
            $this->download_title = $properties['download_title'];
        }

        if (isset($properties['download_info'])) {
            $this->download_info = $properties['download_info'];
        }

        if (isset($properties['download_success'])) {
            $this->download_success = $properties['download_success'];
        }

        if (isset($properties['download_grade'])) {
            $this->download_grade = $properties['download_grade'];
        } else {
            $this->download_grade = false;
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        foreach($files as $file){
            if ($file->name == '') {
                continue;
            }
            if($this->file == $file->name) {
                $this->file_id = $file->id;

                $this->save();
                return array($file->id);
            }
        }

    }

    private function getIcon($file)
    {
        $icon = '';

        if ($file->isAudio()) {
            $icon = 'audio';
        } else if ($file->isImage()) {
            $icon = 'pic';
        } else if ($file->isVideo()) {
            $icon = 'video';
        } else if (mb_strpos($file->mime_type, 'pdf') > -1) {
            $icon = 'pdf';
        } else if (mb_strpos($file->mime_type, 'zip') > -1) {
            $icon = 'archive';
        } else if ((mb_strpos($file->mime_type, 'txt') > -1) || (mb_strpos($file->mime_type, 'document') > -1) || (mb_strpos($file->mime_type, 'msword') > -1) || (mb_strpos($file->mime_type, 'text') > -1)){
            $icon = 'text';
        } else if ((mb_strpos($file->mime_type, 'powerpoint') > -1) || (mb_strpos($file->mime_type, 'presentation') > -1) ){
            $icon = 'ppt';
        }

        return $icon;
    }

}
