<?
namespace Mooc\UI;

class Courseware extends Block {

    function initialize()
    {
        $this->defineField('lastSelected', \Mooc\SCOPE_USER, null);
    }

    function student_view($context = array())
    {
        if (isset($context['selected'])) {
            $this->lastSelected = $context['selected'];
        }

        list($courseware, $chapter, $subchapter, $section) = $this->getSelectedPath($this->lastSelected);

        $active_section = array();
        if ($section) {
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
        }


        return array(
            'user_may_author'   => $this->container['current_user']->canUpdate($this->_model),
            'courseware'        => $courseware->toArray(),
            'chapters'          => $chapters,
            'subchapters'       => $subchapters,
            'sections'          => $sections,
            'active_chapter'    => $chapter    ? $chapter->id    : '',
            'active_subchapter' => $subchapter ? $subchapter->id : '',
            'active_section'    => $active_section);
    }

    function add_structure_handler($data) {

        // we need a valid parent
        if (!isset($data['parent'])) {
            throw new Errors\BadRequest("Parent required.");
        }

        $parent = \Mooc\DB\Block::find($data['parent']);
        if (!$parent || !$parent->isStructuralBlock()) {
            throw new Errors\BadRequest("Invalid parent.");
        }

        if (!$this->container['current_user']->canUpdate($parent)) {
            throw new Errors\AccessDenied();
        }

        // we need a title
        if (!isset($data['title']) || !strlen($data['title']))
        {
            throw new Errors\BadRequest("Title required.");
        }

        $block = $this->createStructure($parent, $data['title']);

        return $block->toArray();
    }



    private function childrenToJSON($collection, $selected, $showFields = false)
    {
        $result = array();
        foreach ($collection as $item) {
            if ($showFields) {
                $block = $this->container['block_factory']->makeBlock($item);
                $json = $block->toJSON();
            } else {
                $json = $item->toArray();
            }

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


    private function createStructure($parent, $title)
    {
        // determine type of new child
        // is there a structural level below the parent?
        $structure_types = \Mooc\DB\Block::getStructuralBlockClasses();
        $index = array_search($parent->type, $structure_types);
        if (!$child_type = $structure_types[$index + 1]) {
            throw new Errors\BadRequest("Unknown child type.");
        }

        $method = "create" . $child_type;

        return $this->$method($parent, $title);
    }

    private function createChapter($parent, $title)
    {
        $chapter = $this->createAnyBlock($parent, 'Chapter', $title);
        $this->createSubchapter($chapter, _('Unterkapitel 1'));
        return $chapter;
    }

    private function createSubchapter($parent, $title)
    {
        $subchapter = $this->createAnyBlock($parent, 'Subchapter', $title);
        $this->createSection($subchapter, _('Abschnitt 1'));
        return $subchapter;
    }

    private function createSection($parent, $title)
    {
        return $this->createAnyBlock($parent, 'Section', $title);
    }

    private function createAnyBlock($parent, $type, $title)
    {
        $block = new \Mooc\DB\Block();
        $block->setData(array(
            'seminar_id' => $this->_model->seminar_id,
            'parent_id'  => $parent->id,
            'type'       => $type,
            'title'      => $title
        ));

        $block->store();

        return $block;
    }
}
