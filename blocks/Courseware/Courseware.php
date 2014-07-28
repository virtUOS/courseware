<?
namespace Mooc\UI\Courseware;

use Mooc\UI\Block;
use Mooc\UI\Errors\AccessDenied;
use Mooc\UI\Errors\BadRequest;

class Courseware extends Block {

    function initialize()
    {
        $this->defineField('lastSelected', \Mooc\SCOPE_USER, null);
    }

    function student_view($context = array())
    {
        $this->lastSelected = $this->getSelected($context);

        list($courseware, $chapter, $subchapter, $section) = $this->getSelectedPath($this->lastSelected);

        $active_section = array();
        if ($section && $this->container['current_user']->canRead($section)) {
            $active_section_block = $this->container['block_factory']->makeBlock($section);
            $active_section = array(
                'id'        => $section->id,
                'title'     => $section->title,
                'parent_id' => $subchapter->id,
                'html'      => $active_section_block->render('student', $context)
            );
        }

        $chapters = $this->childrenToJSON($courseware->children, $chapter->id);

        $subchapters = array();
        if ($chapter) {
            $subchapters = $this->childrenToJSON($chapter->children, $subchapter->id);
        }

        $sections = array();
        if ($subchapter) {
            $sections = $this->childrenToJSON($subchapter->children, $section->id, true);
            $section_nav = $this->getNeighborSections($subchapter->children, $section);
        }


        return array(
            'user_may_author'   => $this->container['current_user']->canUpdate($this->_model),
            'courseware'        => $courseware->toArray(),
            'chapters'          => $chapters,
            'subchapters'       => $subchapters,
            'sections'          => $sections,
            'section_nav'       => $section_nav,
            'active_chapter'    => $chapter    ? $chapter->toArray()    : null,
            'active_subchapter' => $subchapter ? $subchapter->toArray() : null,
            'active_section'    => $active_section);
    }

    function add_structure_handler($data)
    {
        $parent = $this->requireUpdatableParent($data);

        // we need a title
        if (!isset($data['title']) || !strlen($data['title']))
        {
            throw new BadRequest("Title required.");
        }

        $block = $this->createStructure($parent, $data);

        return $block->toArray();
    }


    function update_positions_handler($data)
    {
        $parent = $this->requireUpdatableParent($data);

        // we need some positions
        if (!isset($data['positions']))
        {
            throw new BadRequest("Positions required.");
        }
        $new_positions = array_map("intval", $data['positions']);
        $old_positions = array_map("intval", $parent->children->pluck("id"));

        if (sizeof($new_positions) !== sizeof($old_positions)
            || sizeof(array_diff($new_positions, $old_positions))) {
            throw new BadRequest("Positions required.");
        }

        $parent->updateChildPositions($new_positions);

        // TODO: what to return?
        return $new_positions;
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/courseware/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/courseware/courseware-1.0.xsd';
    }

    private function getSelected($context)
    {
        return isset($context['selected']) ? $context['selected'] : $this->lastSelected;
    }

    private function requireUpdatableParent($data)
    {
        // we need a valid parent
        if (!isset($data['parent'])) {
            throw new BadRequest("Parent required.");
        }

        $parent = \Mooc\DB\Block::find($data['parent']);
        if (!$parent || !$parent->isStructuralBlock()) {
            throw new BadRequest("Invalid parent.");
        }

        if (!$this->container['current_user']->canUpdate($parent)) {
            throw new AccessDenied();
        }

        return $parent;
    }

    private function childrenToJSON($collection, $selected, $showFields = false)
    {
        $result = array();
        foreach ($collection as $item) {
            if (!$this->container['current_user']->canRead($item)) {
                continue;
            }

            if ($showFields) {
                $block = $this->container['block_factory']->makeBlock($item);
                $json = $block->toJSON();
            } else {
                $json = $item->toArray();
            }

            if (!$item->isPublished()) {
                $json['unpublished'] = true;
            }

            $json['dom_title'] = $item->publication_date ? date('d.m.Y', $item->publication_date) : ' ';
            $json['selected'] = $selected == $item->id;
            $result[] = $json;
        }
        return $result;
    }

    private function getSelectedPath($selected)
    {
        $block = $selected instanceof \Mooc\DB\Block ? $selected : \Mooc\DB\Block::find($selected);
        if (!($block && $this->hasMatchingCID($block))) {
            return $this->getDefaultPath();
        }

        $node = $this->getLastStructuralNode($block);

        $ancestors = $node->getAncestors();
        $ancestors[] = $node;
        return $ancestors;
    }

    private function getDefaultPath()
    {
        $ancestors = array();

        // courseware
        $courseware = $this->_model;
        $ancestors[] = $courseware;

        // chapter
        $chapter = $courseware->children->first();
        if (!$chapter) {
            return $ancestors;
        }
        $ancestors[] = $chapter;

        // subchapter
        $subchapter = $chapter->children->first();
        if (!$subchapter) {
            return $ancestors;
        }
        $ancestors[] = $subchapter;

        // section
        $section = $subchapter->children->first();
        if (!$section) {
            return $ancestors;
        }
        $ancestors[] = $section;

        return $ancestors;
    }


    private function getLastStructuralNode($block)
    {
        // got it!
        if ($block->type === 'Section') {
            return $block;
        }

        // search parent
        if (!$block->isStructuralBlock()) {
            return $this->getLastStructuralNode($block->parent);
        }

        // searching downwards... which is actually complicated as
        // there may be no such thing.
        $first_born = $block->children->first();

        if (!$first_born) {
            return $block;
        }

        return $this->getLastStructuralNode($first_born);
    }

    private function hasMatchingCID($block)
    {
        return $block->seminar_id === $this->container['cid'];
    }


    private function createStructure($parent, $data)
    {
        // determine type of new child
        // is there a structural level below the parent?
        $structure_types = \Mooc\DB\Block::getStructuralBlockClasses();
        $index = array_search($parent->type, $structure_types);
        if (!$child_type = $structure_types[$index + 1]) {
            throw new BadRequest("Unknown child type.");
        }

        $method = "create" . $child_type;

        return $this->$method($parent, $data);
    }

    private function createChapter($parent, $data)
    {
        $chapter = $this->createAnyBlock($parent, 'Chapter', $data);
        $this->createSubchapter($chapter, array('title' => _('Unterkapitel 1')));
        return $chapter;
    }

    private function createSubchapter($parent, $data)
    {
        $subchapter = $this->createAnyBlock($parent, 'Subchapter', $data);
        $this->createSection($subchapter, array('title' => _('Abschnitt 1')));
        return $subchapter;
    }

    private function createSection($parent, $data)
    {
        return $this->createAnyBlock($parent, 'Section', $data);
    }

    private function createAnyBlock($parent, $type, $data)
    {
        $block = new \Mooc\DB\Block();
        $block->setData(array(
            'seminar_id'       => $this->_model->seminar_id,
            'parent_id'        => $parent->id,
            'type'             => $type,
            'title'            => $data['title'],
            'publication_date' => $data['publication_date']
        ));

        $block->store();

        return $block;
    }

    private function getNeighborSections($siblings, $active_section)
    {
        $ids = $siblings->pluck('id');
        $index = array_search($active_section->id, $ids);
        return array(
            'prev' => array_key_exists($index - 1, $ids) ? $siblings->find($ids[$index - 1])->toArray() : null,
            'next' => array_key_exists($index + 1, $ids) ? $siblings->find($ids[$index + 1])->toArray() : null
        );
    }
}
