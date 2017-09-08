<?php

namespace Mooc\UI\DiscussionBlock;

use Mooc\UI\Block;
use Mooc\UI\Section\Section;

/**
 */
class DiscussionBlock extends Block
{
    const NAME = 'Diskussion';

    public static function additionalInstanceAllowed($container, Section $section, $subType = null)
    {
        return $container['current_courseware']->getDiscussionBlockActivation();
    }


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
        $this->authorizeUpdate();

        // cannot do anything withough blubber activated in this course
        if ($inactive = !self::blubberActivated($this)) {
            return compact('inactive');
        }

        return array();
    }


    /**
     * {@inheritDoc}
     */
    public function isEditable()
    {
        return false;
    }

    const SUBTYPE_ALL    = 'inall';
    const SUBTYPE_GROUPS = 'ingroups';

    /**
     * {@inheritdoc}
     */
    public static function getSubTypes()
    {
        return array(
            self::SUBTYPE_ALL    => _cw('gemeinsam'),
            self::SUBTYPE_GROUPS => _cw('in Gruppen')
        );
    }

    const REQUIRED_COMMENT_LENGTH = 100;

    public function onCommentCreated($comment)
    {
        if (strlen($comment->description) >= self::REQUIRED_COMMENT_LENGTH) {
            $this->setGrade(1.0);
        }
    }

    /////////////////////
    // PRIVATE HELPERS //
    /////////////////////


    private function getThreadsOfUser()
    {
        $discussion_type = $this->_model->sub_type;

        switch ($discussion_type) {

        default:
        case self::SUBTYPE_ALL:
            return $this->getThreadsOfUserInAll();
            break;


        case self::SUBTYPE_GROUPS:
            return $this->getThreadsOfUserInGroups();
            break;
        }
    }

    private function getThreadsOfUserInAll()
    {
        return array(new GroupDiscussion($this->container['cid'], $this->container['current_user'], $this->id, null));
    }

    private function getThreadsOfUserInGroups()
    {
        $container = $this->container;
        $block_id = $this->id;
        $user_is_author = !$container['current_user']->canUpdate($this->_model);

        // students get only their corresponding statusgruppen
        if ($user_is_author) {

            $groups = $this->getStatusgruppenByCourseAndUser();

            if (sizeof($groups)) {
                $threads = array_values(
                    $groups->map(function ($group) use ($container, $block_id) {
                            return new GroupDiscussion($container['cid'], $container['current_user'], $block_id, $group);
                        }));
            }

            else {
                # TODO: removed for DFB
                # $threads = array(new GroupDiscussion($container['cid'], $container['current_user'], $block_id, null));
                $threads = array();
            }

        }

        // everyone else sees all of them
        else {
            $groups = $this->getStatusgruppenByCourse();

            $threads = array_values(
                $groups->map(function ($group) use ($container, $block_id) {
                        return new GroupDiscussion($container['cid'], $container['current_user'], $block_id, $group);
                    }));

            # TODO: removed for DFB
            # // authors and users w/o groups and everyone else gets the
            # // default group too
            # $threads[] = new GroupDiscussion($container['cid'], $container['current_user'], $block_id, null);
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
