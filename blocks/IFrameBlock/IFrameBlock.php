<?
namespace Mooc\UI\IFrameBlock;

use Mooc\UI\Block;

class IFrameBlock extends Block
{
    const NAME = 'IFrame';

    function initialize()
    {
        $this->defineField('url',    \Mooc\SCOPE_BLOCK, "http://studip.de");
        $this->defineField('height', \Mooc\SCOPE_BLOCK, 600);
    }

    function array_rep() {
        return array(
            'url'    => $this->url,
            'height' => $this->height
        );
    }

    function student_view()
    {
        // on view: grade with 100%
        $this->setGrade(1.0);
        return $this->array_rep();
    }

    function author_view()
    {
        return $this->toJSON();
    }

    function foo_handler($data)
    {
        $this->url    = (string) $data['url'];
        $this->height = (int) $data['height'];
        return $this->array_rep();
    }

    public function export()
    {
        return array('url' => $this->url, 'height' => $this->height);
    }
}
