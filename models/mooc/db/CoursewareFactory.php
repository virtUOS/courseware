<?php
namespace Mooc\DB;

/**
 * @author  <mlunzena@uos.de>
 */
class CoursewareFactory
{

    private $container;

    /**
     * Constructs a new CoursewareFactory
     *
     * @param \Mooc\Container $c  the DI container to use
     */
    public function __construct($c)
    {
        $this->container = $c;
    }

    /**
     * Factory method creating a new prefilled Courseware for a `cid`.
     *
     * @param string $cid  the `cid` of a course or institute to
     *                     create a Courseware for.
     * @return \Mooc\DB\Block  the root block of everything mooc-ish
     *                         of the specified `cid`
     */
    public function makeCourseware($cid)
    {
        // try to find it first
        $courseware = Block::findCourseware($cid);

        // create one, if there is none
        if (!$courseware) {
            $courseware = $this->createCourseware($cid);
        }

        return $courseware;
    }

    private function createCourseware($cid)
    {
        $courseware = $this->createGenericBlock($cid, 'Courseware', _('Neue Courseware'));

        $this->createChapter($courseware, _("Kapitel") . " 1");
        $this->createChapter($courseware, _("Kapitel") . " 2");

        return $courseware;
    }

    private function createChapter($courseware, $title)
    {
        $chapter = $this->createGenericBlock($courseware, 'Chapter', $title);

        $this->createSubchapter($chapter, _("Unterkapitel 1"));

        return $chapter;
    }

    private function createSubchapter($chapter, $title)
    {
        $subchapter = $this->createGenericBlock($chapter, 'Subchapter', $title);

        $this->createSection($subchapter, _("Abschnitt 1"));
        $this->createSection($subchapter, _("Abschnitt 2"));

        return $subchapter;
    }

    private function createSection($subchapter, $title)
    {
        $section = $this->createGenericBlock($subchapter, 'Section', $title);

        $this->createHtmlBlock($section, _("Name des HTML-Blocks"));

        return $section;
    }

    private function createHtmlBlock($section, $title)
    {
        return $this->createGenericBlock($section, 'HtmlBlock', $title);
    }

    private function createGenericBlock($parent, $type, $title)
    {
        if (is_string($parent)) {
            $seminar_id = $parent;
            $parent_id = null;
        }
        else {
            $seminar_id = $parent->seminar_id;
            $parent_id = $parent->id;
        }

        $block = new Block();
        $block->setData(array(
            'seminar_id' => $seminar_id,
            'parent_id'  => $parent_id,
            'type'       => $type,
            'title'      => $title
        ));

        $block->store();

        return $block;
    }
}
