<?php

namespace Mooc\UI\DiscussionBlock;

use Mooc\UI\Block;

/**
 */
class DiscussionBlock extends Block
{
    const NAME = 'Diskussion';

    function initialize()
    {
    }

    function student_view()
    {
        // cannot do anything withough blubber activated in this course
        if ($inactive = !self::blubberActivated($this)) {
            return compact('inactive');
        }

        return array('threads' => $this->getThreadsOfUser());
    }

    function author_view()
    {
        // cannot do anything withough blubber activated in this course
        if ($inactive = !self::blubberActivated($this)) {
            return compact('inactive');
        }

        return array();
    }

    /////////////////////
    // PRIVATE HELPERS //
    /////////////////////


    private function getThreadsOfUser()
    {
        $container = $this->container;
        $block_id = $this->id;

        // students get only their corresponding statusgruppen
        if (!$container['current_user']->canUpdate($this->_model)) {

            $groups = $this->getStatusgruppenByCourseAndUser();

            if (sizeof($groups)) {
                $threads = $groups->map(function ($group) use ($container, $block_id) {
                        return new Discussion($container, $block_id, $group);
                    });
            }

            else {
                $threads = array(new Discussion($container, $block_id, null));
            }

        }

        // everyone else sees all of them
        else {
            $groups = $this->getStatusgruppenByCourse();

            $threads = $groups->map(function ($group) use ($container, $block_id) {
                    return new Discussion($container, $block_id, $group);
            });

            // authors and users w/o groups and everyone else gets the
            // default group too
            $threads[] = new Discussion($container, $block_id, null);
        }

        return $threads;
    }


    // retrieve all the statusgruppen of a user in a course
    private function getStatusgruppenByCourseAndUser()
    {
        $uid = $this->container['current_user_id'];

        // filter by membership
        return $this->getStatusgruppenByCourse()
                    ->filter(function ($group) use ($uid) { return $group->isMember($uid); });
    }

    // retrieve all the statusgruppen of a course
    private function getStatusgruppenByCourse()
    {
        $cid = $this->container['cid'];
        $groups = \SimpleCollection::createFromArray(\Statusgruppen::findBySeminar_id($cid));
        return $groups->orderBy('position ASC');
    }



    // is the Blubber plugin activated in the currently selected course
    private static function blubberActivated($block)
    {
        $plugin_manager = \PluginManager::getInstance();
        $plugin_info = $plugin_manager->getPluginInfo('Blubber');
        return $plugin_manager->isPluginActivated($plugin_info['id'], $block->getModel()->seminar_id);
    }
}
