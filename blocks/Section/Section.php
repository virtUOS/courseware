<?
namespace Mooc\UI;

class Section extends Block {

    function initialize()
    {
    }

    function student_view($context = array())
    {
        $blocks = $this->traverseChildren(
            function ($child, $container) use ($context) {
                $json = $child->toJSON();
                $json['content'] = $child->render('student', $context);
                return $json;
            }
        );


        // block adder
        $content_block_types = array();
        foreach ($this->container['block_factory']->getContentBlockClasses() as $type) {
            $content_block_types[] = compact("type");
        }

        return compact('blocks', 'content_block_types');
    }

    function add_child_handler($data) {

        if (!isset($data['type'])) {
            throw new \RuntimeException();
        }

        // TODO: auth!

        // TODO: valid type?

        $block = new \Mooc\DB\Block();
        $block->setData(array(
            'seminar_id' => $this->_model->seminar_id,
            'parent_id'  => $this->_model->id,
            'type'       => $data['type'],
            'title'      => "Ein weiterer " . $data['type']
        ));

        $block->store();

        return $block->toArray();
    }
}
