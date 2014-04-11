<?
namespace Mooc\UI;

class Section extends Block {

    function initialize()
    {
        $this->defineField('visited', \Mooc\SCOPE_USER, false);
    }

    function student_view($context = array())
    {

        if (!$this->visited) {
            $this->visited = true;
        }

        $title = $this->title;
        $visited = $this->visited;

        $blocks = $this->traverseChildren(
            function ($child, $container) use ($context) {
                $json = $child->toJSON();
                $json['block_content'] = $child->render('student', $context);
                return $json;
            }
        );


        // block adder
        $content_block_types = array();
        foreach ($this->container['block_factory']->getContentBlockClasses() as $type) {
            $content_block_types[] = compact("type");
        }

        return compact('blocks', 'content_block_types', 'title', 'visited');
    }

    function add_content_block_handler($data) {

        if (!isset($data['type'])) {
            throw new Errors\BadRequest("Type required.");
        }

        if (!$this->container['current_user']->canCreate($this->_model)) {
            throw new Errors\AccessDenied();
        }

        $types = $this->container['block_factory']->getContentBlockClasses();
        if (!in_array($data['type'], $types)) {
            throw new Errors\BadRequest("Wrong type.");
        }

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

    function remove_content_block_handler($data) {

        if (!isset($data['child_id'])) {
            throw new Errors\BadRequest("Child ID required");
        }

        $child = $this->_model->children->findOneBy("id", (int) $data['child_id']);
        if (!$child) {
            throw new Errors\BadRequest("No such child");
        }

        if (!$this->container['current_user']->canDelete($child)) {
            throw new Errors\BadRequest("Access denied");
        }

        $child->delete();

        return array("status" => "ok");
    }
}
