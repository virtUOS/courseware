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
            if ((strpos($item['filename'], 'mp4') > -1) || (strpos($item['filename'], 'webm') > -1) || (strpos($item['filename'], 'ogg') > -1)) {
                $document = \StudipDocument::find($item['dokument_id']);
                $item['download_url'] = \URLHelper::getURL( 'sendfile.php',  array('type'=>'0', 'file_id' => $document->id, 'file_name' => $document->name) );
                $filesarray[] = $item;
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
        $sources = json_decode($this->webvideo);
        foreach ($sources as $source) {
            if ($source->file_id != '') {
                $document = new \StudipDocument($source->file_id);
                $files[] = array(
                    'id' => $source->file_id,
                    'name' => $document->name,
                    'description' => $document->description,
                    'filename' => $document->filename,
                    'filesize' => $document->filesize,
                    'url' => $document->url,
                    'path' => get_upload_file_path($source->file_id),
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
            foreach ($webvideo as &$source) {
                if(($source->file_name == $file->name) && ($source->file_id != '')) {
                    $source->file_id = $file->id;
                    $document = \StudipDocument::find($file->id);
                    $source->src = \URLHelper::getURL( 'sendfile.php',  array('type'=>'0', 'file_id' => $document->id, 'file_name' => $document->name) );
                }
            }
        }
        $this->webvideo = json_encode($webvideo);

        $this->save();
    }
}
