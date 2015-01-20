<?php

namespace Mooc\UI\ForumBlock;

use Mooc\UI\Block;
use Mooc\UI\Section\Section;

/**
 * Display the contents of a Blubber stream in a (M)ooc.IP block.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class ForumBlock extends Block
{
    const NAME = 'Diskussion';

    public function student_view()
    {
        // on view: grade with 100%
        $this->setGrade(1.0);

        // check, that correct categories and areas
        #\ForumEntry::
    }

    public function author_view()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function isEditable()
    {
        return false;
    }
}
