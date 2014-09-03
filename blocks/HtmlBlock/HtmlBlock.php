<?
namespace Mooc\UI\HtmlBlock;

use Mooc\UI\Block;

// TODO: lots!
class HtmlBlock extends Block
{
    const NAME = 'Freitext';

    function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
    }

    function student_view()
    {
        $this->setGrade(1.0);
        return array('content' => $this->content);
    }


    function author_view()
    {
        return $this->toJSON();
    }

    /**
     * {@inheritdoc}
     */
    public function exportContents()
    {
        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function importContents($contents)
    {
        $this->content = $contents;
        $this->save();
    }
}
