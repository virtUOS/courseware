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
    const ICON_OPENCAST = 'opencast';
    const ICON_AUDIO = 'audio';
    const ICON_GALLERY = 'gallery';
    const ICON_TASK = 'task';
    const ICON_SEARCH = 'search';
    const ICON_DEFAULT = 'document';
    const FAVORITES_DATAFIELD = '446e9485d92e1eef776a8ccf99849182';

    // definition of precedence of icons
    // larger array index -> higher precedence
    // thus ICON_VIDEO has the highest precedence
    private static $icon_precedences = array(
        self::ICON_DEFAULT, self::ICON_CHAT, self::ICON_TASK, self::ICON_VIDEO, self::ICON_OPENCAST,
        self::ICON_AUDIO, self::ICON_CODE, self::ICON_SEARCH, self::ICON_GALLERY
    );

    private static $custom_icons =  array(
        'doctoral_cap', 'community', 'edit', 'plugin',
        'graph', 'admin', 'billboard', 'category',
        'cloud2', 'comment', 'date', 'edit-small',
        'exclaim', 'favorite', 'file', 'folder-empty2',
        'group2', 'guestbook', 'home', 'key','license',
        'literature', 'news', 'notification2', 'place',
        'print', 'ranking', 'refresh', 'resources',
        'staple', 'star', 'stat', 'studygroup',
        'tag', 'wizard', 'youtube', 'light-bulb'
    );

    // mapping of block types to icons
    private static $map_blocks_to_icons = array(
        'BlubberBlock' => self::ICON_CHAT,
        'ForumBlock' => self::ICON_CHAT,
        'PostBlock' => self::ICON_CHAT,
        'VideoBlock' => self::ICON_VIDEO,
        'OpenCastBlock' => self::ICON_OPENCAST,
        'InteractiveVideoBlock' => self::ICON_VIDEO,
        'AudioBlock' => self::ICON_AUDIO,
        'TestBlock' => self::ICON_TASK,
        'SearchBlock' => self::ICON_SEARCH,
        'CodeBlock' => self::ICON_CODE,
        'CanvasBlock' => self::ICON_GALLERY,
        'GalleryBlock' => self::ICON_GALLERY,
        'BeforeAfterBlock' => self::ICON_GALLERY,
    );

    public function initialize()
    {
        $this->defineField('visited', \Mooc\SCOPE_USER, false);
        $this->defineField('icon', \Mooc\SCOPE_BLOCK, self::ICON_DEFAULT);
        $this->defineField('custom_icon', \Mooc\SCOPE_BLOCK, false);
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
        $can_update = $this->container['current_user']->canUpdate($this);

        $blocks = $this->traverseChildren(
            function (Block $child) use ($context) {
                if (!$this->getCurrentUser()->canRead($child)) {
                    return null;
                }
                $json = $child->toJSON();
                $json['block_content'] = $child->render('student', $context);
                $json['view_name'] = 'student';
                
                if (!$child->getModel()->isVisible()) {
                    $json['invisible'] = true;
                } else  {
                    $json['invisible'] = false;
                }

                return $json;
            }
        );
        foreach ($blocks as &$block){
            $block['make_date'] = date("d.n.Y",$block['mkdate']);
        }
        // block adder
        $block_types = $this->getBlockTypes();
        $content_block_types_function = $block_types['function_blocks'];
        $content_block_types_interaction = $block_types['interaction_blocks'];
        $content_block_types_layout = $block_types['layout_blocks'];
        $content_block_types_multimedia = $block_types['multimedia_blocks'];
        $content_block_types_all = $block_types['all_blocks'];
        $content_block_types_favorite = $block_types['favorite_blocks'];

        return compact(
            'blocks', 
            'content_block_types_function',
            'content_block_types_interaction',
            'content_block_types_layout',
            'content_block_types_multimedia',
            'content_block_types_all',
            'content_block_types_favorite',
            'icon',
            'title', 
            'visited',
            'can_update'
        );
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

        if (!$this->custom_icon) {
            $this->updateIconWithBlock($block);
        }

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

        if (!$this->custom_icon) {
            $this->refreshIcon();
         }

        return array('status' => 'ok');
    }

    public function add_favorites_handler($data)
    {
        if (!$this->container['current_user']->canUpdate($this)) {
            throw new Errors\AccessDenied(_cw('Sie sind nicht berechtigt diese Änderung vorzunehmen.'));
        }
        if (!isset($data['favorites'])) {
            throw new BadRequest('Type required.');
        }

        $user_id = $this->container['current_user_id'];
        \UserConfig::get($user_id)->store('COURSEWARE_FAVORITE_BLOCKS', $data['favorites']);

        return;
    }

    public function set_icon_handler($data)
    {
        if (!$this->container['current_user']->canUpdate($this)) {
            throw new Errors\AccessDenied(_cw('Sie sind nicht berechtigt diese Änderung vorzunehmen.'));
        }
        $this->custom_icon = true;
        if (in_array($data['icon'], $this->allowed_icons())) {
            $this->icon = $data['icon'];
        } else {
            $this->icon = self::ICON_DEFAULT;
        }

        return true;
    }

    public function allowed_icons()
    {
        return array_merge(self::$icon_precedences , self::$custom_icons);
    }

    public function visibility_handler($data)
    {
        if (!$this->container['current_user']->canUpdate($this)) {
            throw new Errors\AccessDenied(_cw('Sie sind nicht berechtigt diese Änderung vorzunehmen.'));
        }
        $child = $this->_model->children->findOneBy('id', (int) $data['block_id']); //Mooc\DB\Block

        $child->visible = $data['visible'];
        return $child->store();
    }

    private function get_favorites()
    {
        $user_id = $this->container['current_user_id'];
        $favs = \UserConfig::get($user_id)->COURSEWARE_FAVORITE_BLOCKS;
        if (!$favs) {
            return false;
        }
        $favs = json_decode($favs[0], true);

        return $favs['blocktypes'];
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
            if ($block != null) {
                $block_files = $block->getFiles();
                if (is_array($block_files)) {
                    $files = array_merge($files, $block_files);
                }
            }
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
            $blockClassConstant = $className.'::BLOCK_CLASS';
            $descriptionConstant = $className.'::DESCRIPTION';
            $hintConstant = $className.'::HINT';

            if (defined($nameConstant)) {
                $readableName = _cw(constant($nameConstant));
            } else {
                $readableName = '';
            }

            if (defined($blockClassConstant)) {
                $blockClass = _cw(constant($blockClassConstant));
            } else {
                $blockClass = '';
            }

            if (defined($descriptionConstant)) {
                $description = _cw(constant($descriptionConstant));
            } else {
                $description = '';
            }

            if (defined($hintConstant)) {
                $hint = _cw(constant($hintConstant));
            } else {
                $hint = '';
            }

            if (!class_exists($className)) {
                continue;
            }
            
            if ($type == 'EvaluationBlock') {
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
                        'name' => $name,
                        'block_class' => $blockClass,
                        'description' => $description,
                        'hint' => $hint
                    );
                }
            } else {
                if ($className::additionalInstanceAllowed($this->container, $this)) {
                    $name = $readableName;
                    $blockTypes[$name] = array(
                        'type' => $type,
                        'sub_type' => null,
                        'name' => $name,
                        'block_class' => $blockClass,
                        'description' => $description,
                        'hint' => $hint
                    );
                }
            }
        }
        ksort($blockTypes);
        $function_blocks = array();
        $interaction_blocks = array();
        $layout_blocks = array();
        $multimedia_blocks = array();
        $all_blocks = array();
        $favorite_blocks = array();
        $favs = $this->get_favorites();
        if (empty($favs)) {
            $favs = array();
        }
        foreach($blockTypes as $key => $value){
            array_push($all_blocks, $value);
            $value_for_favorite = $value;
            if (in_array($value['type'], $favs)){
                $value_for_favorite['selected'] = true;
            } else {
                $value_for_favorite['selected'] = false;
            }
            array_push($favorite_blocks, $value_for_favorite);
            switch ($value['block_class']) {
                case 'function':
                    array_push($function_blocks, $value);
                    break;
                case 'interaction':
                    array_push($interaction_blocks, $value);
                    break;
                case 'layout':
                    array_push($layout_blocks, $value);
                    break;
                case 'multimedia':
                    array_push($multimedia_blocks, $value);
                    break;

            }
        }

        return array(
            'function_blocks' => $function_blocks,
            'interaction_blocks' => $interaction_blocks,
            'layout_blocks' => $layout_blocks,
            'multimedia_blocks' => $multimedia_blocks,
            'all_blocks' => $all_blocks,
            'favorite_blocks' => $favorite_blocks,
        );
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
