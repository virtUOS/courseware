<?php

namespace Mooc\Export\Visitor;

use Mooc\DB\Block;
use Mooc\UI\Courseware\Courseware;
use Mooc\UI\Section\Section;

/**
 * Abstract visitor, provides empty implementations for all methods.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
abstract class AbstractVisitor implements VisitorInterface
{
    /**
     * {@inheritdoc}
     */
    public function startVisitingCourseware(Courseware $courseware)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingCourseware(Courseware $courseware)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingChapter(Block $chapter)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingChapter(Block $chapter)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingSubChapter(Block $chapter)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingSubChapter(Block $chapter)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingSection(Section $section)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingSection(Section $section)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingBlock(\Mooc\UI\Block $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingBlock(\Mooc\UI\Block $block)
    {
    }
}
