<?php

namespace Mooc\UI\VideoBlock;

use Mooc\UI\Block;

/**
 * @property string $url
 */
class VideoBlock extends Block
{
    const NAME = 'Video';

    function initialize()
    {
        $this->defineField('url', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('webvideo', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('webvideosettings', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('videoTitle', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('aspect', \Mooc\SCOPE_BLOCK, 'aspect-169');
    }

    function array_rep() {
        return array(
            'url'    => $this->url,
            'webvideo'    => $this->webvideo, 
            'webvideosettings'    => $this->webvideosettings, 
            'videoTitle'    => $this->videoTitle,
            'aspect' => $this->aspect
        );
    }

    function student_view()
    {
        $this->setGrade(1.0);
        $array = $this->array_rep();
        $array['webvideo'] = json_decode($array['webvideo']);
        return $array;
    }

    function author_view()
    {
        $this->authorizeUpdate();

        return $this->array_rep();
    }

    function save_handler($data)
    {
        $this->authorizeUpdate();

        $this->url = (string) $data['url'];
        $this->webvideo = (string) $data['webvideo'];
        $this->webvideosettings = (string) $data['webvideosettings'];
        $this->videoTitle = (string) $data['videoTitle'];
        $this->aspect = (string) $data['aspect'];

        return $this->array_rep();
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
       return array('url' => $this->url, 'webvideo' => $this->webvideo, 'webvideosettings' => $this->webvideosettings, 'videoTitle' => $this->videoTitle, 'aspect' => $this->aspect);
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
}
