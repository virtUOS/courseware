<?php
namespace Mooc\UI;

class VideoBlock extends Block
{
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
        return $this->toJSON();
    }

    function save_handler($data)
    {
        $this->url = (string) $data['url'];
        return array('url' => $this->url);
    }
}
