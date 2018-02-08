<?php

namespace Mooc\UI\Courseware;

use Mooc\DB\Field;
use Mooc\DB\UserProgress;
use Mooc\DB\Block as DbBlock;
use Mooc\UI\Block;
use Mooc\UI\Errors\BadRequest;

/**
 * @property \Mooc\DB\Block $lastSelected
 */
class Courseware extends Block
{
    const PROGRESSION_FREE = 'free';
    const PROGRESSION_SEQ = 'seq';

    // 'tutor' and 'dozent'  may edit courseware
    const EDITING_PERMISSION_TUTOR = 'tutor';

    // only 'dozent'  may edit courseware
    const EDITING_PERMISSION_DOZENT = 'dozent';

    public function initialize()
    {
        $this->defineField('lastSelected', \Mooc\SCOPE_USER, null);

        // FIXME: this must be stored somewhere else, see https://github.com/virtUOS/courseware/issues/16
        $this->defineField('progression', \Mooc\SCOPE_BLOCK, self::PROGRESSION_FREE);

        // FIXME: this must be stored somewhere else, see https://github.com/virtUOS/courseware/issues/16
        $this->defineField('discussionblock_activation', \Mooc\SCOPE_BLOCK, false);

        // FIXME: this must be stored somewhere else, see https://github.com/virtUOS/courseware/issues/16
        $this->defineField('vipstab_visible', \Mooc\SCOPE_BLOCK, false);

        // FIXME: this must be stored somewhere else, see https://github.com/virtUOS/courseware/issues/16
        $this->defineField('editing_permission', \Mooc\SCOPE_BLOCK, self::EDITING_PERMISSION_TUTOR);

        // FIXME: this must be stored somewhere else, see https://github.com/virtUOS/courseware/issues/16
        $this->defineField('max_tries', \Mooc\SCOPE_BLOCK, 3); // -1 = infinity

        $this->defineField('show_section_nav', \Mooc\SCOPE_BLOCK, true);

        $this->defineField('sections_as_chapters', \Mooc\SCOPE_BLOCK, false);
    }

    public function student_view($context = array())
    {
        $lastSelected = $this->getSelected($context);

        if (!$this->getCurrentUser()->isNobody()) {
            $this->lastSelected = $lastSelected;
        }

        /** @var \Mooc\DB\Block $courseware */
        /** @var \Mooc\DB\Block $chapter */
        /** @var \Mooc\DB\Block $subchapter */
        $tree = $this->getPrunedChapterNodes(list($courseware, $chapter, $subchapter, $section) = $this->getSelectedPath($lastSelected));

        $active_section = array();
        if ($section && $this->getCurrentUser()->canRead($section)) {
            $active_section_block = $this->getBlockFactory()->makeBlock($section);
            $active_section = array(
                'id' => $section->id,
                'title' => $section->title,
                'parent_id' => $subchapter->id,
                'html' => $active_section_block->render('student', $context),
            );
        }

        $section_nav = null;
        if ($subchapter) {
            $section_nav = $this->getNeighborSections($section);
        }

        // prepare active chapter data
        $active_chapter = null;
        if ($chapter) {
            $active_chapter = $chapter->toArray();
            $active_chapter['aside_section'] = $this->findAsideSection($chapter);
        }

        // prepare active subchapter data
        $active_subchapter = null;
        if ($subchapter) {
            $active_subchapter = $subchapter->toArray();
            $active_subchapter['aside_section'] = $this->findAsideSection($subchapter);
        }

        $this->branchComplete($tree);
        $cid = $this->container['cid'];

        return array_merge($tree, array(
            'user_is_nobody'        => $this->getCurrentUser()->isNobody(),
            'user_may_author'       => $this->getCurrentUser()->canUpdate($this->_model),
            'user_is_teacher'       => $this->getCurrentUser()->hasPerm($cid, 'tutor'),
            'section_nav'           => $section_nav,
            'courseware'            => $courseware,
            'active_chapter'        => $active_chapter,
            'active_subchapter'     => $active_subchapter,
            'show_section_nav'      => $this->show_section_nav,
            'sections_as_chapters'  => $this->sections_as_chapters,
            'isSequential'          => $this->progression == 'seq',
            'active_section'        => $active_section, 
            'cw_title'              => $courseware->title
            )
        );
    }

    public function branchComplete(&$tree)
    {
        $subchapters = &$tree['subchapters'];
        foreach ($subchapters as &$subchapter) {
            $subchapterBlock = DbBlock::findOneBySQL('id = ?', array($subchapter['id']));
            $subchapter['complete'] = $this->subchapterComplete($subchapterBlock);
        }
    }

    public function subchapterComplete($subchapterblock)
    {
        $uid = $this->getCurrentUser()->id;
        foreach ($subchapterblock->children as $section) {
            foreach ($section->children as $block) {
                $bid = $block->id;
                $progress = UserProgress::findOneBySQL('block_id = ? AND user_id = ?', array($bid, $uid));
                if (!$progress || ($progress->grade / $progress->max_grade != 1)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function add_structure_handler($data)
    {
        // only authors may add more structure
        $parent = $this->requireUpdatableParent($data);

        // we need a title
        if (!isset($data['title']) || !strlen($data['title'])) {
            throw new BadRequest('Title required.');
        }

        $block = $this->createStructure($parent, $data);

        return $block->toArray();
    }

    public function update_positions_handler($data)
    {
        // only authors may add more structure
        $parent = $this->requireUpdatableParent($data);

        // we need some positions
        if (!isset($data['positions'])) {
            throw new BadRequest('Positions required.');
        }
        $new_positions = array_map('intval', $data['positions']);
        $old_positions = array_map('intval', $parent->children->pluck('id'));

        if (sizeof($new_positions) !== sizeof($old_positions)
            || sizeof(array_diff($new_positions, $old_positions))) {
            throw new BadRequest('Positions required.');
        }

        $parent->updateChildPositions($new_positions);

        // TODO: what to return?
        return $new_positions;
    }

    public function activateAsideSection_handler($data)
    {
        // block_id is required
        if (!isset($data['block_id'])) {
            throw new BadRequest('block_id is required.');
        }

        // there must be such a block
        if (!$chap = \Mooc\DB\Block::find($data['block_id'])) {
            throw new BadRequest('There is no such block.');
        }

        // the block must be a Chapter or Subchapter
        if (!in_array($chap->type, words('Chapter Subchapter'))) {
            throw new BadRequest('Only chapters and subchapters may have aside sections.');
        }

        $title = 'AsideSection for block '.$data['block_id'];
        $section = $this->createAnyBlock(null, 'Section', compact('title'));

        // now store a link to this section
        $field = new Field(array($data['block_id'], '', 'aside_section'));
        $field->content = $section->id;

        $status = $field->store();

        if (!$status) {
            throw new \RuntimeException('Could not activate aside section.');
        }

        return array('status' => 'ok');
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles()
    {
        $files = array();

        foreach ($this->_model->children as $chapter) {
            $files = array_merge($files, $this->getFilesForChapter($chapter));
        }

        return $files;
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

    // set type of course progression
    //
    // 'free': student may navigate to sub/chapters of choice
    //
    // 'seq':  student may only navigate to completed sub/chapters
    //         and to the next sub/chapter after the last completed
    //         sub/chapter
    public function setProgressionType($type)
    {
        if (in_array($type, array(self::PROGRESSION_FREE, self::PROGRESSION_SEQ))) {
            $this->progression = $type;

            return true;
        }

        return false;
    }

    // return this courseware's type of progression
    public function getProgressionType()
    {
        return $this->progression;
    }

    // FIXME: this must be stored somewhere else, see https://github.com/virtUOS/courseware/issues/16
    // set activation of the DiscussionBlock specific to this courseware
    public function setDiscussionBlockActivation($active)
    {
        $active = (bool) $active;

        // 1. activate Blubber plugin for this course
        $plugin_manager = \PluginManager::getInstance();
        $blubber_info = $plugin_manager->getPluginInfo('Blubber');
        $pid = $blubber_info['id'];
        $cid = $this->_model->seminar_id;

        if ($active && !$plugin_manager->isPluginActivated($pid, $cid)) {
            if (!$success = $plugin_manager->setPluginActivated($pid, $cid, $active)) {
                return false;
            }
        }

        // 2. set field 'discussionblock_activation'
        $this->discussionblock_activation = $active;

        // success!
        return true;
    }

    // FIXME: this must be stored somewhere else, see https://github.com/virtUOS/courseware/issues/16
    // set activation of the DiscussionBlock specific to this courseware
    public function getDiscussionBlockActivation()
    {
        return $this->discussionblock_activation;
    }

    // FIXME: this must be stored somewhere else, see https://github.com/virtUOS/courseware/issues/16
    // set perm level of editing permission
    public function setEditingPermission($perm_level)
    {
        if (in_array($perm_level, array(self::EDITING_PERMISSION_TUTOR, self::EDITING_PERMISSION_DOZENT))) {
            $this->editing_permission = $perm_level;

            return true;
        }

        return false;
    }

    // FIXME: this must be stored somewhere else, see https://github.com/virtUOS/courseware/issues/16
    // get perm level of editing permission
    public function getEditingPermission()
    {
        return $this->editing_permission;
    }

    public function setMaxTries($tries)
    {
        $this->max_tries = $tries;
    }

    public function getMaxTries()
    {
        return $this->max_tries;
    }

    public function setVipsTabVisible($active)
    {
        $this->vipstab_visible = $active;
    }

    public function getVipsTabVisible()
    {
        return $this->vipstab_visible;
    }

    public function setShowSectionNav($state)
    {
        $this->show_section_nav = $state;
    }

    public function getShowSectionNav()
    {
        return $this->show_section_nav;
    }

    public function setSectionsAsChapters($state)
    {
        $this->sections_as_chapters = $state;
    }

    public function getSectionsAsChapters()
    {
        return $this->sections_as_chapters;
    }

    ///////////////////////
    // PRIVATE FUNCTIONS //
    ///////////////////////
    // structural blocks may have a field calles 'aside_section'
    // containing the ID of a block of type 'Section' which is shown
    // in the sidebar whenever this structural block is active
    private function findAsideSection($structure_block)
    {
        if ($aside_field = Field::find(array($structure_block->id, '', 'aside_section'))) {
            if ($aside_block = \Mooc\DB\Block::find($aside_field->content)) {
                return array(
                    'id' => $aside_block->id,
                    'title' => $aside_block->title,
                    'parent_id' => $structure_block->id,
                    'html' => $this->getBlockFactory()->makeBlock($aside_block)->render('student', $context),
                );
            }
        }

        return null;
    }

    private function getSelected($context)
    {
        return isset($context['selected']) ? $context['selected'] : $this->lastSelected;
    }

    // get all chapters and the subchapters of the selected chapter
    private function getPrunedChapterNodes($selection)
    {
        list($courseware, $chapter, $subchapter, $section) = $selection;

        $chapter_nodes = $this->getChapterNodes();
        $chapters = $this->childrenToJSON($chapter_nodes[$courseware->id], $chapter->id);

        $subchapters = array();
        if ($chapter) {
            $subchapters = $this->childrenToJSON($chapter_nodes[$chapter->id], $subchapter->id);
        }

        $sections = array();
        if ($subchapter) {
            $sections = $this->childrenToJSON($subchapter->children, $section->id, true);
        }

        return compact('chapters', 'subchapters', 'sections');
    }

    // get all chapters and the subchapters
    private function getChapterNodes()
    {
        $nodes = array_reduce(
                \Mooc\DB\Block::findInCourseByType($this->container['cid'], words('Chapter Subchapter')),
                function ($memo, $item) {
                    if (!isset($memo[$item->parent_id])) {
                        $memo[$item->parent_id] = array();
                    }
                    $memo[$item->parent_id][$item->id] = $item;

                    return $memo;
                },
                array());

        return $nodes;
    }

    private function childrenToJSON($collection, $selected, $showFields = false)
    {
        $result = array();
        if ($collection) {
            foreach ($collection as $item) {
                $result[] = $this->childToJSON($item, $selected, $showFields);
            }
        }

        return array_values(array_filter($result));
    }

    private function childToJSON($child, $selected, $showFields)
    {
        /** @var \Mooc\DB\Block $child */
        if (!$this->getCurrentUser()->canRead($child)) {
            return null;
        }

        if ($showFields) {
            $block = $this->getBlockFactory()->makeBlock($child);
            $json = $block->toJSON();
        } else {
            $json = $child->toArray();
        }

        if (!$child->isPublished()) {
            $json['unpublished'] = true;
        }

        $json['dom_title'] = $child->publication_date ? date('d.m.Y', $child->publication_date) : '';
        $json['selected'] = $selected == $child->id;

        return $json;
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

    /**
     * @param \Mooc\DB\Block $block
     *
     * @return mixed
     */
    private function getLastStructuralNode($block)
    {
        // got it!
        if ($block->type === 'Section') {
            // normal section
            if ($block->parent_id) {
                return $block;
            }

            // aside section
            else {
                // find aside's "parent" sub/chapter
                // TODO: gruseliger Hack, um das Unter/Kapitel zu finden, in dem die Section eingehängt ist.
                $field = current(\Mooc\DB\Field::findBySQL('user_id = "" AND name = "aside_section" AND json_data = ?', array(json_encode($block->id))));

                return $field->block;
            }
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

    /**
     * @param $parent
     * @param $data
     *
     * @return \Mooc\DB\Block
     *
     * @throws \Mooc\UI\Errors\BadRequest
     */
    private function createStructure($parent, $data)
    {
        // determine type of new child
        // is there a structural level below the parent?
        $structure_types = \Mooc\DB\Block::getStructuralBlockClasses();
        $index = array_search($parent->type, $structure_types);
        if (!$child_type = $structure_types[$index + 1]) {
            throw new BadRequest('Unknown child type.');
        }

        $method = 'create'.$child_type;

        return $this->$method($parent, $data);
    }

    private function createChapter($parent, $data)
    {
        $chapter = $this->createAnyBlock($parent, 'Chapter', $data);
        $this->createSubchapter($chapter, array('title' => _cw('Unterkapitel 1')));

        return $chapter;
    }

    private function createSubchapter($parent, $data)
    {
        $subchapter = $this->createAnyBlock($parent, 'Subchapter', $data);
        $this->createSection($subchapter, array('title' => _cw('Abschnitt 1')));

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
            'seminar_id' => $this->_model->seminar_id,
            'parent_id' => is_object($parent) ? $parent->id : $parent,
            'type' => $type,
            'title' => $data['title'],
            'publication_date' => $data['publication_date'],
        ));

        $block->store();

        return $block;
    }

    /**
     * @param \Mooc\DB\Block $siblings
     * @param \Mooc\DB\Block $active_section
     *
     * @return array
     */
    public function getNeighborSections($active_section)
    {
        // next
        for ($node = $active_section; !$next && $node; $node = $node->parent) {
            $next = $node->nextSibling();
        }
        if (isset($next)) {
            $next = $next->toArray();
        }

        // prev
        for ($node = $active_section; !$prev && $node; $node = $node->parent) {
            $prev = $node->previousSibling();
        }
        if (isset($prev)) {
            $prev = $prev->toArray();
        }

        return compact('prev', 'next');
    }

    private function getFilesForChapter(\Mooc\DB\Block $chapter)
    {
        $files = array();

        foreach ($chapter->children as $subChapter) {
            $files = array_merge($files, $this->getFilesForSubChapter($subChapter));
        }

        return $files;
    }

    private function getFilesForSubChapter(\Mooc\DB\Block $subChapter)
    {
        $files = array();

        foreach ($subChapter->children as $section) {
            /** @var \Mooc\UI\Section\Section $block */
            $block = $this->getBlockFactory()->makeBlock($section);
            $files = array_merge($files, $block->getFiles());
        }

        return $files;
    }
}
