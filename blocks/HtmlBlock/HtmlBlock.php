<?
namespace Mooc\UI\HtmlBlock;

use Mooc\UI\Block;

// TODO: lots!
class HtmlBlock extends Block
{
    const NAME = 'Freitext';

    function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, "Hello World!");
        // $this->content = new StringField(BLOCK_SCOPE, "Hello World!");
    }

    function student_view()
    {
        return array('content' => $this->content);
    }


    function author_view()
    {
        return $this->toJSON();
    }

    function foo_handler($data)
    {
        $this->content = (string) $data['content'];
        return array("content" => $this->content);
    }
}
