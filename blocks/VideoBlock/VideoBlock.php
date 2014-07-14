<?php

namespace Mooc\UI\VideoBlock;

use Mooc\UI\Block;

class VideoBlock extends Block
{
    const NAME = 'Video';

    function initialize()
    {
        $this->defineField('url', \Mooc\SCOPE_BLOCK, '');
    }

    function student_view()
    {
        // on view: grade with 100%
        $this->setGrade(1.0);
        return array('url' => $this->url);
    }

    function author_view()
    {
        return array('url' => $this->url);
    }

    function save_handler($data)
    {
        $this->url = (string) $data['url'];
        return array('url' => $this->url);
    }

    /**
     * {@inheritdoc}
     */
    public function export()
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
}
