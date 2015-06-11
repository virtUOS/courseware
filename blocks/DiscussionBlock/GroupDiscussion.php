<?php

namespace Mooc\UI\DiscussionBlock;

/**
 * Subclass of Discussion creating/finding blubber threads (context =
 * course) containing comments between that users of a group of a course.
 */
class GroupDiscussion extends  Discussion
{

    public function __construct($cid, $user, $block_id, $group)
    {
        $this->block_id  = $block_id;
        $this->group     = $group;
        parent::__construct($cid, $user);
    }

    protected function getDefaultDescription()
    {
        return $this->group
            ? sprintf("Gruppendiskussion '%s'", $this->group->name)
            : "Diskussion";
    }

    protected function getDefaultName()
    {
        return $this->generateID();
    }

    protected function generateID()
    {
        return sprintf('%s-%s', $this->block_id, isset($this->group) ? $this->group->id : 'null');
    }
}
