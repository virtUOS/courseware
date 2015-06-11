<?php

namespace Mooc\UI\DiscussionBlock;

/**
 * Subclass of Discussion creating/finding blubber threads (context =
 * course) containing comments between that user and the lecturers of
 * the course.
 */
class LecturerDiscussion extends  Discussion
{
    protected function getDefaultDescription()
    {
        return sprintf("Teilnehmerkommunikation mit '%s'", $this->user->getFullName());
    }

    protected function getDefaultName()
    {
        return $this->generateID();
    }

    protected function generateID()
    {
        return sprintf("user-%s", $this->user->username);
    }
}
