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
            $document = \StudipDocument::find($this->audio_id);
            if ($document) {
                $access = $document->checkAccess($this->container['current_user_id']);
                if ($document->url == "") {
                    $audio_file = "../../sendfile.php?type=0&file_id=".$document->id."&file_name=".$document->name;
                } else {
                    $audio_file = "../../sendfile.php?type=6&file_id=".$document->id."&file_name=".$document->name;
                }
            }
        } else {
            $access = true;
            $audio_file = $this->audio_file;
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
        $db = \DBManager::get();
        $stmt = $db->prepare('
            SELECT 
                * 
            FROM 
                dokumente 
            WHERE 
                seminar_id = :seminar_id
            ORDER BY 
                name
        ');
        $stmt->bindParam(':seminar_id', $this->container['cid']);
        $stmt->execute();
        $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $filesarray = array();
        foreach ($response as $item) {
            if ((strpos($item['filename'], 'mp3') > -1) || (strpos($item['filename'], 'ogg') > -1) || (strpos($item['filename'], 'wav') > -1)) {
                $filesarray[] = $item;
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
        $document = new \StudipDocument($this->audio_id);
        $files[] = array(
            'id' => $this->audio_id,
            'name' => $document->name,
            'description' => $document->description,
            'filename' => $document->filename,
            'filesize' => $document->filesize,
            'url' => $document->url,
            'path' => get_upload_file_path($this->audio_id),
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
        if ($this->audio_source == "cw") {
            $this->setFileId($this->audio_file_name);
        } else {
            if (isset($properties['audio_file'])) {
                $this->audio_file = $properties['audio_file'];
            }
        }
        $this->save();
    }

    private function setFileId($file_name)
    {
        $cid = $this->container['cid'];
        $document = current(\StudipDocument::findBySQL('filename = ? AND seminar_id = ?', array($file_name, $cid)));
        $this->audio_id = $document->id;
        if ($document->url == "") {
            $this->audio_file = '../../sendfile.php?type=0&file_id='.$document->id.'&file_name='.$document->name;
        } else {
            $this->audio_file = '../../sendfile.php?type=6&file_id='.$document->id.'&file_name='.$document->name;
        }

        return;
    }

    public function importContents($contents, array $files)
    {
        $file = reset($files);
        if (($this->audio_source == 'cw') && ($file->id == $this->audio_id)) {
            $this->save();
        }
    }
}
