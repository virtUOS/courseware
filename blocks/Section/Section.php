<?
namespace Mooc\UI;

class Section extends Block {

    const ICON_VIDEO   = 'video';
    const ICON_TASK    = 'task';
    const ICON_DEFAULT = 'document';

    // definition of precedence of icons
    // larger array index -> higher precedence
    // thus ICON_VIDEO has the highest precedence
    private static $icon_precedences = array(self::ICON_DEFAULT, self::ICON_TASK, self::ICON_VIDEO);

    // mapping of block types to icons
    private static $map_blocks_to_icons = array(
        'VideoBlock' => self::ICON_VIDEO,
        'TestBlock'  => self::ICON_TASK
    );

    function initialize()
    {
        $this->defineField('visited', \Mooc\SCOPE_USER, false);
        $this->defineField('icon',    \Mooc\SCOPE_BLOCK, self::ICON_DEFAULT);
    }

    function student_view($context = array())
    {

        if (!$this->visited) {
            $this->visited = true;
        }

        $icon    = $this->icon;
        $title   = $this->title;
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

        return compact('blocks', 'content_block_types', 'icon', 'title', 'visited');
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

        $this->updateIconWithBlock($block);

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

        $this->refreshIcon();

        return array("status" => "ok");
    }

    private function updateIconWithBlock($new_block)
    {
        if (!isset(self::$map_blocks_to_icons[$new_block->type])) {
            return;
        }

        $icon_for_block = self::$map_blocks_to_icons[$new_block->type];
        $precedence = array_search($icon_for_block, self::$icon_precedences);
        $current_precedence = array_search($this->icon, self::$icon_precedences);

        if ($precedence > $current_precedence) {
            $this->icon = $icon_for_block;
        }
    }

    private function refreshIcon()
    {
        $highest_icon = self::ICON_DEFAULT;
        $highest_precedence = 0;

        foreach ($this->_model->children as $block) {
            $icon = isset(self::$map_blocks_to_icons[$block->type])
                ? self::$map_blocks_to_icons[$block->type]
                : self::ICON_DEFAULT;

            $precedence = array_search($icon, self::$icon_precedences);
            if ($precedence > $highest_precedence) {
                $highest_icon       = $icon;
                $highest_precedence = $precedence;
            }
        }
        $this->icon = $highest_icon;
    }
}
