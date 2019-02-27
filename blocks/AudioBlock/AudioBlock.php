<?php

namespace Mooc\UI\AudioBlock;

use Mooc\UI\Block;

class AudioBlock extends Block
{
    const NAME = 'Audio';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Spielt eine Audiodatei aus dem Dateibereich oder von einer URL ab';

    public function initialize()
    {
        $this->defineField('audio_description', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('audio_source', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('audio_file', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('audio_file_name', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('audio_id', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }

        if ($this->audio_source == "cw") {
            $file = \FileRef::find($this->audio_id);
            if ($file) {
                $audio_file = $file->getDownloadURL();
                $access = ($file->terms_of_use->fileIsDownloadable($file, false)) ? true : false;
            }

        } else {
            $audio_file = $this->audio_file;
            $access = true;
        }

        return array_merge(
            $this->getAttrArray(),
            array(
                'audio_played' => $this->container['current_user']->isNobody() ? 1 : $this->getProgress()['grade'],
                'audio_access' => $access,
                'audio_file' => $audio_file,
            )
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        $files_arr = $this->showFiles();
        $id = $this->audio_id;
        $no_files = empty($files_arr['userfilesarray']) && empty($files_arr['coursefilesarray']) && ($files_arr['audio_id_found'] == false) && empty($id);
        if((!$files_arr['audio_id_found']) && (!empty($id))){
            $other_user_file = array('id' => $this->audio_id, 'name' => $this->audio_file);
        } else {
            $other_user_file = false;
        }

        return array_merge(
            $this->getAttrArray(), 
            array(
                'audio_files_user' => $files_arr['userfilesarray'], 
                'audio_files_course' => $files_arr['coursefilesarray'], 
                'no_audio_files' => $no_files, 
                'other_user_file' => $other_user_file, 
                'audio_file' => $this->audio_file
            )
        );
    }

    public function preview_view()
    {

        return array('audio_file' => $this->audio_file);
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset($data['audio_description'])) {
            $this->audio_description = \STUDIP\Markup::purifyHtml((string) $data['audio_description']);
        }
        if (isset($data['audio_source'])) {
            $this->audio_source = (string) $data['audio_source'];
        }
        if (isset($data['audio_file_name'])) {
            $this->audio_file_name = (string) $data['audio_file_name'];
        }
        if (isset($data['audio_id'])) {
            $this->audio_id = (string) $data['audio_id'];
        }
        if (isset($data['audio_file'])) {
            if ($this->audio_source == 'recorder') {
                $this->store_recording($data['audio_file']);
        } else {
                $this->audio_file = \STUDIP\Markup::purifyHtml((string) $data['audio_file']);
            }
        }

        return;
    }

    public function play_handler($data)
    {
        $this->setGrade(1.0);

        return array();
    }

    private function store_recording($audio) 
    {
        global $user;

        $audio = explode(',', $audio)[1];
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);
        //create file in temp dir
        if ($this->audio_description == '') {
            $filename = 'Courseware-Aufnahme-'.date("d.m.Y-H:i", time()).'.ogg';
        } else {
            $filename = trim($this->audio_description).'-'.date("d.m.Y-H:i", time()).'.ogg';
        }
        file_put_contents($tempDir.'/'.$filename, base64_decode($audio));
        // get personal root folder
        $root_folder = \Folder::findTopFolder($GLOBALS['SessionSeminar']);
        $parent_folder = \FileManager::getTypedFolder($root_folder->id);
        $subfolders = $parent_folder->getSubfolders();
        $cw_folder = null;
        // search courseware upload folder
        foreach($subfolders as $subfolder) {
            if ($subfolder->name == 'Courseware-Upload') {
                $cw_folder = $subfolder;
            }
        }
        // create courseware upload folder
        if ($cw_folder == null) {
            $request = array('name' => 'Courseware-Upload', 'description' => 'folder for courseware content');
            $new_folder = new \CoursePublicFolder();
            $new_folder->setDataFromEditTemplate($request);
            $new_folder->user_id = $user->id;
            $cw_folder = $parent_folder->createSubfolder($new_folder);
        }
        $folder = \FileManager::getTypedFolder($cw_folder->id);
        // create studip file
        $audio_file = [
                'name'     => $filename,
                'type'     => 'audio/ogg',
                'tmp_name' => $tempDir.'/'.$filename,
                'size'     => filesize($tempDir.'/'.$filename),
                'user_id'  => $user->id
            ];
        
        $new_reference = $folder->createFile($audio_file);

        $this->audio_source = 'cw';
        $this->audio_id = $new_reference->id;
        $this->audio_file_name = $new_reference->name;
    }

    private function showFiles()
    {
        $coursefilesarray = array();
        $userfilesarray = array();
        $course_folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        $user_folders =  \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $audio_id_found = false;

        foreach ($course_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isAudio()) && (!$ref->isLink())) {
                    $coursefilesarray[] = $ref;
                }
                if($ref->id == $this->audio_id) {
                    $audio_id_found = true;
                }
            }
        }

        foreach ($user_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isAudio()) && (!$ref->isLink())) {
                    $userfilesarray[] = $ref;
                }
                if($ref->id == $this->audio_id) {
                    $audio_id_found = true;
                }
            }
        }

        return array('coursefilesarray' => $coursefilesarray, 'userfilesarray' => $userfilesarray, 'audio_id_found' => $audio_id_found);
    }

    private function getAttrArray()
    {
        return array(
            'audio_description' => $this->audio_description,
            'audio_source' => $this->audio_source,
            'audio_file_name' => $this->audio_file_name,
            'audio_id' => $this->audio_id
        );
    }

    public function exportProperties()
    {
       return array_merge(
            $this->getAttrArray(),
            array('audio_file' => $this->audio_file)
        );
    }

    public function getFiles()
    {
        
        if ($this->audio_source != 'cw') {
            return;
        }
        if ($this->audio_id == '') {
            return;
        }
        $file_ref = new \FileRef($this->audio_id);
        $file = new \File($file_ref->file_id);
        
        $files[] = array(
            'id' => $this->audio_id,
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
        return 'http://moocip.de/schema/block/audio/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/audio/audio-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['audio_description'])) {
            $this->audio_description = $properties['audio_description'];
        }
        if (isset($properties['audio_source'])) {
            $this->audio_source = $properties['audio_source'];
        }
        if (isset($properties['audio_file_name'])) {
            $this->audio_file_name = $properties['audio_file_name'];
        }
        if (isset($properties['audio_file'])) {
                $this->audio_file = $properties['audio_file'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        foreach($files as $file){
            if ($file->name == '') {
                continue;
            }
            if($this->audio_file_name == $file->name) {
                $this->audio_id = $file->id;
                if ($this->audio_source == 'cw') {
                    $this->audio_file = $file->getDownloadURL();
                    $this->save();

                    return array($file->id);
                }
            }
        }
    }
}
