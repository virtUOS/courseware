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
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
interface VisitorInterface
{
    public function startVisitingCourseware(Courseware $courseware);

    public function endVisitingCourseware(Courseware $courseware);

    public function startVisitingChapter(Block $chapter);

    public function endVisitingChapter(Block $chapter);

    public function startVisitingSubChapter(Block $chapter);

    public function endVisitingSubChapter(Block $chapter);

    public function startVisitingSection(Section $section);

    public function endVisitingSection(Section $section);

    public function startVisitingBlubberBlock(BlubberBlock $block);

    public function endVisitingBlubberBlock(BlubberBlock $block);

    public function startVisitingHtmlBlock(HtmlBlock $block);

    public function endVisitingHtmlBlock(HtmlBlock $block);

    public function startVisitingIFrameBlock(IFrameBlock $block);

    public function endVisitingIFrameBlock(IFrameBlock $block);

    public function startVisitingTestBlock(TestBlock $block);

    public function endVisitingTestBlock(TestBlock $block);

    public function startVisitingVideoBlock(VideoBlock $block);

    public function endVisitingVideoBlock(VideoBlock $block);
}
