<?php

namespace Mooc\UI\AudioGalleryBlock;

use Mooc\UI\Block;
use Mooc\DB\Field;

class AudioGalleryBlock extends Block
{
    const NAME = 'Audio Gallery';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'AudioGalleryBlock';

    public function initialize()
    {
        $this->defineField('audio_gallery_content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('audio_gallery_user_recording', \Mooc\SCOPE_USER, '');
    }

    public function student_view()
    {
        global $user;
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }

        return array_merge(
            $this->getAttrArray(),
            array(
                'audio_records' => $this->get_records()['audio_records'],
                'user_record' => $this->get_records()['user_record'],
                'isTeacher' => $this->container['current_user']->canUpdate($this)
            )
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge(
            $this->getAttrArray(), 
            array()
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset($data['audio_gallery_content'])) {
            $this->audio_gallery_content =  $data['audio_gallery_content'];
        }

        return;
    }

    private function get_records() {
        global $user;

        $user_record = array();
        $audio_records = array();
        $fields = Field::findBySQL('block_id = ? AND name = ?', array($this->id, 'audio_gallery_user_recording'));
        foreach($fields as $field){
            $json_data = json_decode($field['json_data'], true);
            $json_data['user_avatar'] = \Avatar::getAvatar($field['user_id'])->getUrl(\Avatar::NORMAL);
            if ($field['user_id'] == $user->id) {
                array_push($user_record, $json_data);
            } else {
                array_push($audio_records, $json_data);
            }
        }

        usort($audio_records, function ($a,$b) {return ($a['mkdate'] <= $b['mkdate']) ? -1 : 1;});

        if(empty($user_record)) {
            array_push($user_record, array(
                'file_ref_id' => false,
                'file_name' => false,
                'file_url' => false,
                'user_id' => $user->id,
                'user_name' => $user->vorname.' '.$user->nachname,
                'user_avatar' => \Avatar::getAvatar($user->id)->getUrl(\Avatar::NORMAL)
            ));
        }

        return array('user_record' => $user_record, 'audio_records' => $audio_records);
    }

    public function store_recording_handler(array $data) 
    {
        global $user;
        $audio = $data['audio_file'];
        $audio = explode(',', $audio)[1];
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);
        //create file in temp dir
        $filename = 'Courseware-Aufnahme-'.date("d.m.Y-H:i", time()).'.ogg';
        file_put_contents($tempDir.'/'.$filename, base64_decode($audio));
        // get personal root folder
        
        $root_folder = \Folder::findTopFolder($user->id);
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
            $new_folder = new \PublicFolder();
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
        $this->audio_gallery_user_recording = array(
            'file_ref_id' => $new_reference->id,
            'file_name' => $new_reference->name,
            'file_url' => $new_reference->getDownloadURL(),
            'audio_length' => $data['audio_length'],
            'user_id' => $user->id,
            'user_name' => $user->vorname.' '.$user->nachname,
            'mkdate' => time()
        );
        $this->setGrade(1.0);
    }

    public function delete_record_handler(array $data) 
    {
        $uid = $data['uid'];

        if (($this->container['current_user']->id != $uid)&&(!$this->container['current_user']->canUpdate($this))) {
            throw new \InvalidArgumentException(_cw("Sie sind nicht berechtigt diese Aufnahme zu löschen."));
        } else { 
            $field = Field::findOneBySQL('block_id = ? AND user_id = ?AND name = ?', array($this->id, $uid, 'audio_gallery_user_recording'));
            if ($field->delete()) {
                $this->setGrade(0.0);
            } else {
                throw new \InvalidArgumentException(_cw("Aufnahme konnte nicht gelöscht werden."));
            }

            return ;
        }
    }

    private function getAttrArray()
    {
        return array(
            'audio_gallery_content' => $this->audio_gallery_content
        );
    }

    public function exportProperties()
    {
       return array_merge(
            $this->getAttrArray(),
            array()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/audio_gallery/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/audio_gallery/audio_gallery-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['audio_gallery_content'])) {
            $this->audio_gallery_content = $properties['audio_gallery_content'];
        }

        $this->save();
    }

}
