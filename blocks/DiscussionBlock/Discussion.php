<?php

namespace Mooc\UI\DiscussionBlock;

/**
 */
abstract class Discussion
{

    public function __construct($cid, $user)
    {
        $this->cid    = $cid;
        $this->user   = $user;
        $this->thread = $this->findOrCreateBlubberThread();
    }

    abstract protected function getDefaultDescription();

    abstract protected function getDefaultName();

    abstract protected function generateID();

    ////////////////////
    // PRIVATE HELPER //
    ////////////////////

    private function generateMD5()
    {
        return md5($this->generateID());
    }


    private function findOrCreateBlubberThread()
    {
        if (!$thread = \BlubberThread::find($this->generateMD5())) {
            $thread = $this->createBlubberThread();
        }

        return $thread;
    }


    private function createBlubberThread()
    {
        $thread_id = $this->generateMD5();
        $thread = new \BlubberThread($thread_id);

        $data = array(
            'context_type' => 'course',
            'thread_id'      => $thread_id,
            //'parent_id'    => 0,
            'context_id'   => $this->cid,
            'user_id'      => $this->user->id,
            //'name'         => $this->getDefaultName(),
            //'description'  => $this->getDefaultDescription()
        );
        array_walk($data, function ($val, $key) use ($thread) { $thread[$key] = $val; });

        if (!$thread->store()) {
            throw new \RuntimeException("Could not store thread.");
        }

        return $thread;
    }
}