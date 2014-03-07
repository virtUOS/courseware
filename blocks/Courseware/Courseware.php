<?
namespace Mooc\UI;

class Courseware extends Block {

    function initialize()
    {
    }

    // TODO: $context einführen!
    function student_view($context = array())
    {
        $section = $this->getSectionFor($context['selected']);
        list($courseware, $chapter, $subchapter) = $this->getAncestors($section);

        $chapters = array();
        foreach ($courseware->children as $chap) {
            $json = $chap->toArray();
            $json['selected'] = $chapter->id == $chap->id;
            $chapters[] = $json;
        }


        $subchapters = array();
        if ($chapter) {
            foreach ($chapter->children as $sub) {
                $json = $sub->toArray();
                $json['selected'] = $subchapter->id == $sub->id;
                $subchapters[] = $json;
            }
        }


        $sections = array();
        if ($subchapter) {
            foreach ($subchapter->children as $sec) {
                $json = $sec->toArray();
                $json['selected'] = $section->id == $sec->id;
                $sections[] = $json;
            }
        }

        if ($section) {
            $active_section_block = $this->container['block_factory']->makeBlock($section);
            $active_section = array(
                'id'   => $section->id,
                'html' => $active_section_block->render('student', $context)
            );
        }

        return array(
            'courseware'     => $courseware->toArray(),
            'chapters'       => $chapters,
            'subchapters'    => $subchapters,
            'sections'       => $sections,
            'active_section' => $active_section);
    }

    private function getAncestors($section)
    {
        if (!$section) {
            return $this->getDefaultPath();
        }

        return $section->getAncestors();
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


    private function getSectionFor($selected)
    {
        $block = $selected instanceof \Mooc\DB\Block ? $selected : \Mooc\DB\Block::find($selected);

        if (!($block && $this->hasMatchingCID($block))) {
            return null;
        }

        // got it!
        if ($block->type === 'Section') {
            return $block;
        }

        // search parent
        if (!$block->isStructuralBlock()) {
            return $this->getSectionFor($block->parent);
        }

        // searching downwards... which is actually complicated as
        // there may be no such thing.
        $first_born = $block->children->first();

        if (!$first_born) {
            return null;
        }

        return $this->getSectionFor($first_born);
    }

    /*
    private function getDefaultSection()
    {
        return current(
            \Mooc\DB\Block::findBySQL(
                'seminar_id = ? AND type = "Section" ORDER BY parent_id, position',
                array($this->container['cid'])));
    }
    */

    private function hasMatchingCID($block)
    {
        return $block->seminar_id === $this->container['cid'];
    }
}
