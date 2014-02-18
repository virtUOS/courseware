<?
namespace Mooc\UI;

class Section extends Block {

    function initialize()
    {
    }

    function student_view($context = array())
    {
        $data = array();

        $data['blocks'] = $this->traverseChildren(
            function ($child, $container) use ($context) {
                $json = $child->toJSON();
                $json['content'] = $child->render('student', $context);
                return $json;
            }
        );

        return $data;
    }

}
