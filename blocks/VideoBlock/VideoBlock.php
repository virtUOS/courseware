<?php

namespace Mooc\UI\VideoBlock;

use Mooc\UI\Block;

/**
 * @property string $url
 */
class VideoBlock extends Block
{
    const NAME = 'Video';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Spielt ein Video aus dem Dateibereich oder von einer URL ab';

    public function initialize()
    {
        $this->defineField('url', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('webvideo', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('webvideosettings', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('videoTitle', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('aspect', \Mooc\SCOPE_BLOCK, 'aspect-169');
    }

    private function array_rep() {
        return array(
            'url'               => $this->url,
            'webvideo'          => $this->webvideo, 
            'webvideosettings'  => $this->webvideosettings, 
            'videoTitle'        => $this->videoTitle,
            'aspect'            => $this->aspect
        );
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $this->setGrade(1.0);
        $array = $this->array_rep();
        $array['webvideo'] = json_decode($array['webvideo']);

        return $array;
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $video_files = $this->showFiles();
        if (empty($video_files)) {
            $video_files = false;
        }

        return array_merge($this->array_rep(), array(
            'video_files' => $video_files
        ));
    }

    public function save_handler($data)
    {
        $this->authorizeUpdate();
        $this->url = (string) $data['url'];
        $this->webvideo = (string) $data['webvideo'];
        $this->webvideosettings = (string) $data['webvideosettings'];
        $this->videoTitle = \STUDIP\Markup::purifyHtml((string) $data['videoTitle']);
        $this->aspect = (string) $data['aspect'];

        return $this->array_rep();
    }

    private function showFiles()
    {
        $filesarray = array();
        $folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        $user_folders =  \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $folders = array_merge($folders, $user_folders);

        foreach ($folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if ($ref->isVideo())  {
                    $filesarray[] = $ref;
                }
            }
        }

        return $filesarray;
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return $this->array_rep();
    }

    public function getFiles()
    {
        if ($this->webvideo == '') {
            return array();
        }
        $sources = json_decode($this->webvideo);
        foreach ($sources as $source) {
            if ($source->file_id != '') {
                $file_ref = new \FileRef($source->file_id);
                $file = new \File($file_ref->file_id);
                $files[] = array(
                    'id' => $file_ref->id,
                    'name' => $file_ref->name,
                    'description' => $file_ref->description,
                    'filename' => $file->name,
                    'filesize' => $file->size,
                    'url' => $file->getURL(),
                    'path' => $file->getPath()
                );
            }
        }

        return $files;
    }
    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/video/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/video/video-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['url'])) {
            $this->url = $properties['url'];
        }
        
        if (isset($properties['webvideo'])) {
            $this->webvideo = $properties['webvideo'];
        }
        
        if (isset($properties['webvideosettings'])) {
            $this->webvideosettings = $properties['webvideosettings'];
        }

        if (isset($properties['aspect'])) {
            $this->aspect = $properties['aspect'];
        }
        
        if (isset($properties['videoTitle'])) {
            $this->videoTitle = $properties['videoTitle'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        $webvideo = json_decode($this->webvideo);
        foreach($files as $file){
            if ($file->name == '') {
                continue;
            }
            foreach ($webvideo as &$source) {
                if(($source->file_name == $file->name) && ($source->file_id != '')) {
                    $source->file_id = $file->id;
                    $source->src = $file->getDownloadURL();
                }
            }
        }
        $this->webvideo = json_encode($webvideo);

        $this->save();
    }
}
