<?php

namespace Mooc\UI\Section;

use Mooc\UI\Block;
use Mooc\UI\Errors\BadRequest;
use Mooc\UI\Courseware\Courseware;

/**
 * @property bool   $visited
 * @property string $icon
 */
class Section extends Block
{
    const ICON_CHAT = 'chat';
    const ICON_CODE = 'code';
    const ICON_VIDEO = 'video';
    const ICON_AUDIO = 'audio';
    const ICON_GALLERY = 'gallery';
    const ICON_TASK = 'task';
    const ICON_SEARCH = 'search';
    const ICON_DEFAULT = 'document';

    // definition of precedence of icons
    // larger array index -> higher precedence
    // thus ICON_VIDEO has the highest precedence
    private static $icon_precedences = array(self::ICON_DEFAULT, self::ICON_CHAT, self::ICON_TASK, self::ICON_VIDEO, self::ICON_AUDIO, self::ICON_CODE, self::ICON_SEARCH, self::ICON_GALLERY);

    // mapping of block types to icons
    private static $map_blocks_to_icons = array(
        'BlubberBlock'  => self::ICON_CHAT,
        'ForumBlock'    => self::ICON_CHAT,
        'PostBlock'     => self::ICON_CHAT,
        'VideoBlock'    => self::ICON_VIDEO,
        'AudioBlock'    => self::ICON_AUDIO,
        'TestBlock'     => self::ICON_TASK,
        'SearchBlock'   => self::ICON_SEARCH,
        'CodeBlock'     => self::ICON_CODE,
        'GalleryBlock'  => self::ICON_GALLERY,
    );

    public function initialize()
    {
        $this->defineField('visited', \Mooc\SCOPE_USER, false);
        $this->defineField('icon', \Mooc\SCOPE_BLOCK, self::ICON_DEFAULT);
    }

    public function student_view($context = array())
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }

        if (!$this->container['current_user']->isNobody() && !$this->visited) {
            $this->visited = true;
        }

        $icon = $this->icon;
        $title = $this->title;
        $visited = $this->visited;

        $blocks = $this->traverseChildren(
            function (Block $child) use ($context) {
                $json = $child->toJSON();
                $json['block_content'] = $child->render('student', $context);
                $json['view_name'] = 'student';

                return $json;
            }
        );
        foreach ($blocks as &$block){
            $block['make_date'] = date("d.n.Y",$block['mkdate']);
        }
        // block adder
        $content_block_types_basic = $this->getBlockTypes()['basic_blocks'];
        $content_block_types_advanced = $this->getBlockTypes()['advanced_blocks'];
        
        return compact('blocks', 'content_block_types_basic', 'content_block_types_advanced', 'icon', 'title', 'visited');
    }

    public function add_content_block_handler($data)
    {
        if (!$this->container['current_user']->canCreate($this)) {
            throw new Errors\AccessDenied(_cw('Sie sind nicht berechtigt Blöcke anzulegen.'));
        }

        if (!isset($data['type'])) {
            throw new BadRequest('Type required.');
        }

        $types = $this->getBlockFactory()->getContentBlockClasses();
        if (!in_array($data['type'], $types)) {
            throw new BadRequest('Wrong type.');
        }

        $className = '\Mooc\UI\\'.$data['type'].'\\'.$data['type'];

        if (!call_user_func(array($className, 'additionalInstanceAllowed'), $this->container, $this, $data['sub_type'])) {
            throw new BadRequest('No additional '.$data['type'].' allowed');
        }

        $block = new \Mooc\DB\Block();
        $block->setData(array(
            'seminar_id' => $this->_model->seminar_id,
            'parent_id' => $this->_model->id,
            'type' => $data['type'],
            'sub_type' => $data['sub_type'],
            'title' => 'Ein weiterer '.$data['type'],
            'position' => $block->getNewPosition($this->_model->id)
        ));

        $block->store();

        $this->updateIconWithBlock($block);

        /** @var \Mooc\UI\Block $uiBlock */
        $uiBlock = $this->getBlockFactory()->makeBlock($block);
        $data = $block->toArray();
        $data['editable'] = $uiBlock->isEditable();

        return $data;
    }

    public function remove_content_block_handler($data)
    {
        if (!$this->container['current_user']->canUpdate($this)) {
            throw new Errors\AccessDenied(_cw('Sie sind nicht berechtigt Blöcke zu löschen.'));
        }

        if (!isset($data['child_id'])) {
            throw new BadRequest('Child ID required');
        }

        $child = $this->_model->children->findOneBy('id', (int) $data['child_id']);
        if (!$child) {
            throw new BadRequest('No such child');
        }

        $child->delete();

        $this->refreshIcon();

        return array('status' => 'ok');
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

    /////////////////////
    // PRIVATE HELPERS //
    /////////////////////

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
                $readableName = _cw(constant($nameConstant));
            }

            if (!class_exists($className)) {
                continue;
            }

            $subTypes = call_user_func(array($className, 'getSubTypes'));

            if (count($subTypes) > 0) {
                foreach ($subTypes as $subType => $name) {
                    if (!$className::additionalInstanceAllowed($this->container, $this, $subType)) {
                        continue;
                    }
                    $name = $readableName.' ('.$name.')';
                    $blockTypes[$name] = array(
                        'type' => $type,
                        'sub_type' => $subType,
                        'name' => $name
                    );
                }
            } else {
                if ($className::additionalInstanceAllowed($this->container, $this)) {
                    $name = $readableName;
                    $blockTypes[$name] = array(
                        'type' => $type,
                        'sub_type' => null,
                        'name' => $name
                    );
                }
            }
        }
        ksort($blockTypes);
        $basic_blocks = array();
        $advanced_blocks = array();
        foreach($blockTypes as $key => $value){
            if (in_array($value['type'], array('HtmlBlock', 'VideoBlock', 'PostBlock',  'TestBlock') )) {
                array_push($basic_blocks, $value);
            } else {
                array_push($advanced_blocks, $value);
            }
        }

        return array('basic_blocks' =>$basic_blocks, 'advanced_blocks' => $advanced_blocks);
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
                $highest_icon = $icon;
                $highest_precedence = $precedence;
            }
        }
        $this->icon = $highest_icon;
    }

}
