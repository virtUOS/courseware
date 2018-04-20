<?php

namespace Mooc\UI\AudioBlock;

use Mooc\UI\Block;

class AudioBlock extends Block
{
    const NAME = 'Audio';

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
                $access = ($file->terms_of_use->download_condition == 0) ? true : false;
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

        return array_merge($this->getAttrArray(), array('audio_files' => $this->showFiles(), 'audio_file' => $this->audio_file));
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset($data['audio_description'])) {
            $this->audio_description = (string) $data['audio_description'];
        }
        if (isset($data['audio_source'])) {
            $this->audio_source = (string) $data['audio_source'];
        }
        if (isset($data['audio_file'])) {
            $this->audio_file = (string) $data['audio_file'];
        }
        if (isset($data['audio_file_name'])) {
            $this->audio_file_name = (string) $data['audio_file_name'];
        }
        if (isset($data['audio_id'])) {
            $this->audio_id = (string) $data['audio_id'];
        }

        return;
    }

    public function play_handler($data)
    {
        $this->setGrade(1.0);

        return array();
    }

    private function showFiles()
    {
        $filesarray = array();
        $folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        
        foreach ($folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if ($ref->isAudio())  {
                    $filesarray[] = $ref;
                }
            }
        }

        return $filesarray;
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
            return array();
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
            if($this->audio_file_name == $file->name) {
                $this->audio_id = $file->id;
                if ($this->audio_source == 'cw') {
                    $this->audio_file = $file->getDownloadURL();
                    $this->save();
                }
            }
        }
    }
}
