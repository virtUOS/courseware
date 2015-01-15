<?php

namespace Mooc\UI\VideoBlock;

use Mooc\UI\Block;

/**
 * @property string $url
 */
class VideoBlock extends Block
{
    const NAME = 'Video';

    const YOUTUBE_PATTERN = '/^.*(youtu.be\/|v\/|embed\/|watch\?|youtube.com\/|user\|watch\?|feature=player_embedded\&|\/[^#]*#([^\/]*?\/)*)\??v?=?([^#\&\?]*).*/i';

    function initialize()
    {
        $this->defineField('url', \Mooc\SCOPE_BLOCK, '');
    }

    function student_view()
    {
        // on view: grade with 100%
        $this->setGrade(1.0);

        return array('url' => $this->cleanUpYouTubeUrl($this->url));
    }

    function author_view()
    {
        return array('url' => $this->cleanUpYouTubeUrl($this->url));
    }

    function save_handler($data)
    {
        $this->requireUpdatableParent(array('parent' => $this->getModel()->parent_id));

        $this->url = static::cleanUpYouTubeUrl((string) $data['url']);
        return array('url' => $this->url);
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return array('url' => $this->url);
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

        $this->save();
    }

    /**
     * Cleans up YouTube URLs if necessary.
     *
     * YouTube does not support URLs like http://www.youtube.com/watch?v=<ID>
     * to be embedded in iframes. The URL pattern has to be
     * http://www.youtube.com/embed/<ID>
     *
     * @param string $url The URL to clean up
     *
     * @return string The cleaned up URL
     */
    public static function cleanUpYouTubeUrl($url)
    {
        if (!preg_match(self::YOUTUBE_PATTERN, $url)) {
            return $url;
        }

        $parts = parse_url($url);

        if (!isset($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $params);
        $parts['path'] = '/embed/'.$params['v'];
        unset($params['v']);

        $url = $parts['scheme'].'://'.$parts['host'].$parts['path'];

        if (count($params) > 0) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }
}
