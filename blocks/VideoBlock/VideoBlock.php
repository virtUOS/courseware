<?php

namespace Mooc\UI\VideoBlock;

use Mooc\UI\Block;

/**
 * @property string $url
 */
class VideoBlock extends Block
{
    const NAME = 'Video';

    private $openCastActive;
    private $openCastEpisodes;
    /** @var  \OpenCast */
    private $openCastPlugin;

    function initialize()
    {
        $this->defineField('url', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('webvideo', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('webvideosettings', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('videoTitle', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('aspect', \Mooc\SCOPE_BLOCK, 'aspect-169');
        $this->defineField('opencastVideo', \Mooc\SCOPE_BLOCK, '');

        /** @var \OpenCast $ocPlugin */
        $this->openCastPlugin = \PluginManager::getInstance()->getPlugin('OpenCast');
        $this->openCastActive = \PluginManager::getInstance()->isPluginActivated($this->openCastPlugin->getPluginId(), $GLOBALS['SessionSeminar']);

        if($this->openCastActive) {
            require_once $this->openCastPlugin->getPluginPath()."/models/OCCourseModel.class.php";
            $ocCourse = new \OCCourseModel($GLOBALS['SessionSeminar']);
            $this->openCastEpisodes = $ocCourse->getEpisodes();
        }

    }

    function array_rep() {
        return array(
            'url'    => $this->url,
            'webvideo'    => $this->webvideo, 
            'webvideosettings'    => $this->webvideosettings, 
            'videoTitle'    => $this->videoTitle,
            'aspect' => $this->aspect,
            'opencastVideo' => $this->opencastVideo,
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
        $this->opencastVideo = (string) $data['opencastVideo'];

        return $this->array_rep();
    }

    function getOpenCastVideos_handler()
    {
        if($this->openCastActive) {
            return $this->openCastEpisodes;
        } else {
            return null;
        }
    }

    function getOpencastURL_handler($data)
    {
        if($this->openCastActive) {
            require_once $this->openCastPlugin->getPluginPath() . "/classes/OCRestClient/SearchClient.php";

            $searchClient = new  \SearchClient();

            $url = $searchClient->getBaseURL() . "/engage/theodul/ui/core.html?id=" . $data['opencastVideo'] . "&mode=embed";

            if (get_config("OPENCAST_STREAM_SECURITY")) {
                require_once $this->openCastPlugin->getPluginPath() . "/classes/OCRestClient/SecurityClient.php";
                /** @var \SecurityClient $securityClient */
                $securityClient = new \SecurityClient();
                $url = $securityClient->signURL($url);
            }

            return array("url" => $url);
        } else {
            return null;
        }
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
