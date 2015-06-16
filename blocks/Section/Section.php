<?
namespace Mooc\UI\Section;

use Mooc\UI\Block;
use Mooc\UI\Errors\AccessDenied;
use Mooc\UI\Errors\BadRequest;

/**
 * @property bool   $visited
 * @property string $icon
 */
class Section extends Block {

    const ICON_CHAT    = 'chat';
    const ICON_VIDEO   = 'video';
    const ICON_TASK    = 'task';
    const ICON_DEFAULT = 'document';

    // definition of precedence of icons
    // larger array index -> higher precedence
    // thus ICON_VIDEO has the highest precedence
    private static $icon_precedences = array(self::ICON_DEFAULT, self::ICON_CHAT, self::ICON_TASK, self::ICON_VIDEO);

    // mapping of block types to icons
    private static $map_blocks_to_icons = array(
        'BlubberBlock' => self::ICON_CHAT,
        'VideoBlock'   => self::ICON_VIDEO,
        'TestBlock'    => self::ICON_TASK
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
            function (Block $child) use ($context) {
                $json = $child->toJSON();
                $json['block_content'] = $child->render('student', $context);
                return $json;
            }
        );

        // block adder
        $content_block_types = $this->getBlockTypes();

        return compact('blocks', 'content_block_types', 'icon', 'title', 'visited');
    }

    /**
     * View rendering buttons to add new blocks.
     *
     * @return array The available block types
     */
    function block_types_view()
    {
        return array('content_block_types' => $this->getBlockTypes());
    }

    function add_content_block_handler($data) {

        if (!isset($data['type'])) {
            throw new BadRequest("Type required.");
        }

        if (!$this->getCurrentUser()->canCreate($this->_model)) {
            throw new AccessDenied();
        }

        $types = $this->getBlockFactory()->getContentBlockClasses();
        if (!in_array($data['type'], $types)) {
            throw new BadRequest("Wrong type.");
        }

        $className = '\Mooc\UI\\'.$data['type'].'\\'.$data['type'];

        if (!call_user_func(array($className, 'additionalInstanceAllowed'), $this, $data['sub_type'])) {
            throw new BadRequest('No additional '.$data['type'].' allowed');
        }

        $block = new \Mooc\DB\Block();
        $block->setData(array(
            'seminar_id' => $this->_model->seminar_id,
            'parent_id'  => $this->_model->id,
            'type'       => $data['type'],
            'sub_type'   => $data['sub_type'],
            'title'      => "Ein weiterer " . $data['type']
        ));

        $block->store();

        $this->updateIconWithBlock($block);

        /** @var \Mooc\UI\Block $uiBlock */
        $uiBlock = $this->getBlockFactory()->makeBlock($block);
        $data = $block->toArray();
        $data['editable'] = $uiBlock->isEditable();

        return $data;
    }

    function remove_content_block_handler($data) {

        if (!isset($data['child_id'])) {
            throw new BadRequest("Child ID required");
        }

        $child = $this->_model->children->findOneBy("id", (int) $data['child_id']);
        if (!$child) {
            throw new BadRequest("No such child");
        }

        if (!$this->getCurrentUser()->canDelete($child)) {
            throw new BadRequest("Access denied");
        }

        $child->delete();

        $this->refreshIcon();

        return array("status" => "ok");
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles()
    {
        $files = array();

        foreach ($this->_model->children as $child) {
            /** @var \Mooc\UI\Block $block */
            $block = $this->getBlockFactory()->makeBlock($child);
            $files = array_merge($files, $block->getFiles());
        }

        return $files;
    }

    public function updateIconWithBlock($new_block)
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

    /**
     * Returns the available block types.
     *
     * @return array The available block types
     */
    private function getBlockTypes()
    {
        $blockTypes = array();



        foreach ($this->getBlockFactory()->getContentBlockClasses() as $type) {
            $className = '\Mooc\UI\\'.$type.'\\'.$type;
            $readableName = $type;
            $nameConstant = $className.'::NAME';

            if (defined($nameConstant)) {
                $readableName = constant($nameConstant);
            }

            $subTypes = call_user_func(array($className, 'getSubTypes'));

            if (count($subTypes) > 0) {
                foreach ($subTypes as $subType => $name)  {

                    if (!$className::additionalInstanceAllowed($this, $subType)) {
                        continue;
                    }

                    $blockTypes[] = array(
                        'type'     => $type,
                        'sub_type' => $subType,
                        'name'     => $readableName.' ('.$name.')'
                    );
                }
            } else {
                if ($className::additionalInstanceAllowed($this)) {
                    $blockTypes[] = array(
                        'type'     => $type,
                        'sub_type' => null,
                        'name'     => $readableName
                    );
                }
            }
        }

        return $blockTypes;
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
