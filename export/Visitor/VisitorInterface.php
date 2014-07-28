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

    public function startVisitingBlock(\Mooc\UI\Block $block);

    public function endVisitingBlock(\Mooc\UI\Block $block);
}
