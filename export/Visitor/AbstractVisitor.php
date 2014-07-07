<?php

namespace Mooc\Export\Visitor;

use Mooc\DB\Block;
use Mooc\UI\BlubberBlock\BlubberBlock;
use Mooc\UI\Courseware\Courseware;
use Mooc\UI\HtmlBlock\HtmlBlock;
use Mooc\UI\IFrameBlock\IFrameBlock;
use Mooc\UI\Section\Section;
use Mooc\UI\TestBlock\TestBlock;
use Mooc\UI\VideoBlock\VideoBlock;

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
    public function startVisitingBlubberBlock(BlubberBlock $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingBlubberBlock(BlubberBlock $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingHtmlBlock(HtmlBlock $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingHtmlBlock(HtmlBlock $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingIFrameBlock(IFrameBlock $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingIFrameBlock(IFrameBlock $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingTestBlock(TestBlock $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingTestBlock(TestBlock $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function startVisitingVideoBlock(VideoBlock $block)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function endVisitingVideoBlock(VideoBlock $block)
    {
    }
}
