<?php
namespace Mooc\UI\FolderBlock;

use Mooc\UI\Block;

class FolderBlock extends Block 
{
    const NAME = 'Dateiordner';
    const BLOCK_CLASS = 'function';
    const DESCRIPTION = 'Stellt einen Ordner aus dem Dateibereich zur Verfügung';

    public function initialize()
    {
        $this->defineField('folder_content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $user = $this->container['current_user'];
        $user_is_authorized = false;
        $files = array();
        $folder_content = json_decode($this->folder_content);
        $folder = \Folder::find($folder_content->folder_id);
        if ($user->hasPerm($this->container['cid'], 'tutor')) {
            $user_is_authorized = true;
        }

        if ($folder) {
            $folder_available = true;
            $folder_hidden = false;
            $folder_homework = false;
            $folder_group = false;
            $folder_timed = false;
            $typed_folder = $folder->getTypedFolder();
            $folder_type = $typed_folder->folder_type;
            switch ($folder_type) {
                case 'HiddenFolder':
                    if($folder->data_content['download_allowed'] != 1) {
                        $folder_hidden = true;
                    }
                    break;
                case  'HomeworkFolder':
                    $folder_content->allow_upload = true;
                    $folder_content->folder_type = $folder_type;
                    $this->folder_content = json_encode($folder_content);
                    $this->save();
                    $folder_homework = true;
                    break;
                case  'CourseGroupFolder':
                    $folder_group = true;
                    break;
                case  'TimedFolder':
                    $folder_timed = [];
                    if ($typed_folder->start_time == 0) {
                        $folder_timed['start'] = '&#8734;';
                    } else {
                        $folder_timed['start'] = strftime('%x', $typed_folder->start_time);
                    }
                    if ($typed_folder->end_time == 0) {
                        $folder_timed['end'] = '&#8734;';
                    } else {
                        $folder_timed['end'] = strftime('%x', $typed_folder->end_time);
                    }
                    
                    // $folder_type_change = 'Dieser Ordner ist für die Studenten nur vom ' .  ($typed_folder->start_time > 0 ? strftime('%x %X', $typed_folder->start_time) : 'unbegrenzt') . ' bis zum ' . ($typed_folder->end_time > 0 ? strftime('%x %X', $typed_folder->end_time) : 'unbegrenzt') . ' sichtbar.';
                    break;
            }

            if($typed_folder->isVisible($user->id)) {
                $viewable = true;
                $folder_name = $folder->name;

                if($typed_folder->isReadable($user->id)) {
                    $files = $this->showFiles($folder_content->folder_id);
                    $homework_files = false;
                } else {
                    if($folder_type === 'HomeworkFolder') {
                        foreach ($typed_folder->getFiles() as $file) {
                            if($file->user_id == $user->id) {
                                $homework_files['details'][] = array('name' => $file->name,
                                'date' => strftime('%x %X', $file->chdate));
                            }
                        }
                    }
                    $files = array();
                }
                $allow_upload = ($folder_content->allow_upload && $typed_folder->isWritable($user->id));
            } else {
                $viewable = false;
            }
        } else {
            $folder_available = false;
            $folder_name = '';
            $files = array();
            $allow_upload = false;
            $homework_files = false;
            $no_folder = true;
        }

        $this->setGrade(1.0);

        return array_merge($this->getAttrArray(), array(
            'folder_available'=> $folder_available,
            'folder_name' => $folder_name,
            'folder_type_change' => $folder_type_change,
            'folder_type' => $folder_type,
            'files' => $files,
            'allow_upload' => $allow_upload,
            'viewable' => $viewable,
            'homework_files' => $homework_files,
            'folder_homework' => $folder_homework,
            'folder_hidden' => $folder_hidden,
            'folder_group' => $folder_group,
            'folder_timed' => $folder_timed,
            'user_is_authorized' => $user_is_authorized,
            'file_counter' => sizeOf($files),
            'isAuthor' => $this->getUpdateAuthorization(),
            'no_folder' => $no_folder
        ));
    }

    public function preview_view()
    {
        $folder_content = json_decode($this->folder_content);
        $folder = \Folder::find($folder_content->folder_id);

        return array('folder_name' => $folder->name);
    }

    public function author_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $folder_content = json_decode($this->folder_content);
        $folder_id = $folder_content->folder_id;
        
        $this->authorizeUpdate();
        $folders =  \Folder::findBySQL('range_id = ? AND folder_type != ?', array($this->container['cid'], 'RootFolder'));
        $folderarray = [];
        foreach($folders as $folder) {
            $folder = $folder->getTypedFolder();
            $disabled = '';
            if($folder->folder_type == 'HiddenFolder') {
                if($folder->data_content['download_allowed'] != 1) {
                    $disabled = 'disabled';
                }
            }
            $folderarray[] = array(
                'id' => $folder->id,
                'name' => $folder->name,
                'folder_type' => $folder->folder_type,
                'size' => sizeOf($folder->getFiles()),
                'disabled' => $disabled
            );
        }

        $root_folder = \Folder::findOneBySQL('range_id = ? AND folder_type = ?', array($this->container['cid'], 'RootFolder'));
        $root_folder->name = 'Hauptordner';
        array_unshift($folders, $root_folder);
        $other_user_folder = false;

        if(!empty($folder_id)) {
            $folder_found = false;
            foreach($folders as $folder) {
                if ($folder->id == $folder_id) {
                    $folder_found = true;
                    break;
                }
            }
            if(!$folder_found) {
                $other_user_folder[] = \Folder::find($folder_id);
            }
        }

        return array_merge(
            $this->getAttrArray(), 
            array(
                'folders' => $folderarray,
                'other_user_folder' => $other_user_folder,
                'allow_upload' => $folder_content->allow_upload
            )
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset ($data['folder_content'])) {
            $this->folder_content = (string) $data['folder_content'];
        }

        return;
    }

    private function showFiles($folder_id)
    {
        $folder = \Folder::find($folder_id);
        $filesarray = array();
        if ($folder) {
            $folder = $folder->getTypedFolder();
            $files = $folder->getFiles();

        } else {
            return $filesarray;
        }
        foreach ($files as $item) {
            if($folder->folder_type === 'HomeworkFolder') {
                $user = \User::find($item->user_id)->getFullname();
            }
            $filesarray[] = array('id' => $item->getId(),
                            'name' => $item->getFilename(), 
                            'icon' => $this->getIcon($item->getId()), 
                            'url' => $this->getFileURL($item),
                            'downloadable' => $item->isDownloadable($GLOBALS['user']->id),
                            'user' => $user);
        }
        return $filesarray;
    }

    public function getIcon($file_id) {
        $icon = '';
        $file = \FileRef::find($file_id);
        if ($file) {
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
            } else {
                $icon = '';
            }
        }


        return $icon;
    }

    private function getAttrArray() 
    {
        $folder_content = json_decode($this->folder_content);
        return array(
            'folder_id' => $folder_content->folder_id,
            'folder_title' => $folder_content->folder_title,
            'allow_upload' => $folder_content->allow_upload
        );
    }

    private function getFileInfos()
    {
        $folder_content = json_decode($this->folder_content);
        $folder = \Folder::find($folder_content->folder_id);
        if ($folder == null) {
            return array('name' =>  array(), 'id' =>array());
        }
        $typed_folder = $folder->getTypedFolder();
        if ($typed_folder->folder_type == 'HomeworkFolder') {
            return array('name' =>  array(), 'id' =>array());
        }
        $files_id = array();
        $files_name = array();
        foreach($this->showFiles($folder_content->folder_id) as $file) {
            array_push($files_id, $file['id']);
            array_push($files_name, $file['name']);
        }

        return array('name' =>  $files_name, 'id' => $files_id);
    }

    public function exportProperties()
    {
        $folder_content = json_decode($this->folder_content);
        if($folder_content) {
            $file_infos = $this->getFileInfos();
            $folder_content->file_ids = $file_infos['id'];
            $folder_content->file_names = $file_infos['name'];
            $folder = \Folder::find($folder_content->folder_id);
            if ($folder == null) {
                $folder_content->folder_type = '';
            } else {
                $typed_folder = $folder->getTypedFolder();
                $folder_content->folder_type = $typed_folder->folder_type;
                $folder_content->folder_name = $folder->name;
            }
        } else {
           return;
        }

        return array(
             'folder_content' => json_encode($folder_content),
        );
    }

    public function getHtmlExportData()
    {
        $folder_content = json_decode($this->folder_content);
        $filesarray = $this->showFiles($folder_content->folder_id);
        foreach($filesarray as &$file) {
            $file['path'] = './' . $file['id'] . '/' . $file['name'];
        }

        return array_merge($this->getAttrArray(), array('files' => $filesarray));
    }

    public function getFiles()
    {
        $files = array();
        $file_ids = $this->getFileInfos()['id'];

        foreach ($file_ids as $file_id) {
            if ($file_id == '') {
                continue;
            }
            $file_ref = new \FileRef($file_id);
            $file = new \File($file_ref->file_id);

            array_push( $files, array (
                'id' => $file_ref->id,
                'name' => $file_ref->name,
                'description' => $file_ref->description,
                'filename' => $file->name,
                'filesize' => $file->size,
                'url' => $this->isFileAnURL($file_ref),
                'path' => $file->getPath()
            ));
        }

        return $files;
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/folder/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/folder/folder-1.0.xsd';
    }

    private function createFolder($folder_name, $folder_type='StandardFolder')
    {
        global $user;
        $cid = $this->container['cid'];
        $courseware_folder = \Folder::findOneBySQL('range_id = ? AND name LIKE ? ORDER BY mkdate DESC', array( $cid , '%Courseware-Import%'));
        $parent_folder = \FileManager::getTypedFolder($courseware_folder->id);
        if($folder_name == '') {$folder_name = $this->id;}
        $request = array('name' => $folder_name, 'description' => 'gallery folder');
        if ($folder_type == '') {
            $new_folder = new StandardFolder();
        } else {
            $new_folder = new $folder_type();
        }
        if ($folder_type == 'HiddenFolder') {
            $request['hidden_folder_download_allowed'] = 1;
        }
        $new_folder->setDataFromEditTemplate($request);
        $new_folder->user_id = $user->id;
        $folder = $parent_folder->createSubfolder($new_folder);

        return $folder->id;
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['folder_content'])) {
            $folder_content = json_decode($properties['folder_content']);
            $folder_content->folder_id = $this->createFolder($folder_content->folder_name, $folder_content->folder_type);
            $this->folder_content = json_encode($folder_content);
        }

        $this->save();
    }

    private function moveFiles()
    {
        $current_user = \User::findCurrent();
        $folder_content = json_decode($this->folder_content);
        $file_ids = $folder_content->file_ids;
        $folder = \FileManager::getTypedFolder($folder_content->folder_id);
        foreach ($file_ids as $file_id) {
            $file_ref = new \FileRef($file_id);
            $file_type = new \StandardFile($file_ref);
            \FileManager::moveFile($file_type, $folder, $current_user);
        }
    }

    public function importContents($contents, array $files)
    {
        $used_files = array();
        $folder_content = json_decode($this->folder_content);
        if ($folder_content) {
            $file_ids = array();

            foreach($files as $file){
                if ($file->name == '') {
                    continue;
                }
                if(in_array($file->name, $folder_content->file_names)) {
                    array_push($file_ids , array($file->id));
                    array_push($used_files , $file->id);
                }
            }
            $folder_content->file_ids = $file_ids;
            $this->folder_content = json_encode($folder_content);
            $this->save();

            $this->moveFiles();

            $this->save();
        }

        return $used_files;
    }
}
