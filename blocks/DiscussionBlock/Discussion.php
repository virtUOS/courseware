<?php

namespace Mooc\UI\DiscussionBlock;

/**
 */
class Discussion
{

    public function __construct($container, $block_id, $group)
    {
        $this->container = $container;
        $this->block_id  = $block_id;
        $this->group     = $group;
        $this->thread    = $this->findOrCreateBlubberThread();
    }

    ////////////////////
    // PRIVATE HELPER //
    ////////////////////

    private function generateThreadId()
    {
        $preid = sprintf('%s-%s',
                         $this->block_id,
                         isset($this->group)
                           ? $this->group->id
                           : 'null');

        return md5($preid);
    }

    private function findOrCreateBlubberThread()
    {
        if (!$thread = \BlubberPosting::find($id = $this->generateThreadId())) {
            $thread = $this->createBlubberThread($id);
        }

        return $thread;
    }


    private function createBlubberThread($thread_id)
    {
        $cid       = $this->container['cid'];
        $author_id = $this->container['current_user_id'];
        $content   = $this->group
                   ? sprintf("Gruppendiskussion der Gruppe '%s'", $this->group->name)
                   : "Gruppendiskussion";

        $thread = new \BlubberPosting($thread_id);

        $data = array(
            'context_type' => 'course',
            'root_id'      => $thread_id,
            'parent_id'    => 0,
            'seminar_id'   => $cid,
            'user_id'      => $author->id,
            'name'         => $this->block_id . '-' . ($this->group ? $this->group->id : 'null'),
            'description'  => $content
        );
        array_walk($data, function ($val, $key) use ($thread) { $thread[$key] = $val; });

        if (!$thread->store()) {
            throw new \RuntimeException("Could not store thread.");
        }

        return $thread;
    }
}
