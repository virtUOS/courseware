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
}
