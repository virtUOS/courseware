<?php

require_once __DIR__.'/vendor/autoload.php';

/**
 * Observes posted Notifications from the NotificationCenter to
 * implement advices on core code:
 *
 *   - As soon as a student writes a long enough comment in a
 *     DiscussionBlock, he gets graded for it.
 *   - The core Blubber plugin when used in the DiscussionBlock sends
 *     PersonalNotifications with wrong URLs. They have to be rewritten.
 *
 * @author  <mlunzena@uos.de>
 */
class CoursewareObserver extends StudIPPlugin implements SystemPlugin
{
    /**
     * Initialize this plugin and observe these Notifications:
     *
     *   - PostingHasSaved
     *   - PersonalNotificationsWillStore
     */
    public function __construct()
    {
        parent::__construct();

        $this->observeBlubber();
        $this->observePersonalNotifications();
    }

    // observe PostingHasSaved
    private function observeBlubber()
    {
        NotificationCenter::addObserver($this, 'afterBlubberPost', 'PostingHasSaved');
    }

    // observe PersonalNotificationsWillStore (v3.4)
    private function observePersonalNotifications()
    {
        NotificationCenter::addObserver($this, 'beforePersonalNotifications', 'PersonalNotificationsWillStore');
    }

    // if this was a blubber from a GroupDiscussion,
    // store it and grade the block for the author
    function afterBlubberPost($event, $posting)
    {
        // the courseware plugin must be activated in that course
        $courseware_plugin = PluginEngine::getPlugin('Courseware');
        if (!$courseware_plugin->isActivated($posting->seminar_id)) {
            return;
        }

        if ($block_id = $this->isGroupDiscussion($posting)) {
            $this->afterGroupDiscussionBlubber($posting, $block_id);
        }

        else if ($username = $this->isLecturerDiscussion($posting)) {
            $this->afterLecturerDiscussionBlubber($posting, $username);
        }
    }

    // retrieve Courseware block, store it and give its author a grade
    private function afterGroupDiscussionBlubber($posting, $block_id)
    {
        if ($block = $this->getBlockFromGroupDiscussionBlubber($block_id)) {
            $this->storeBlubber($posting, self::GROUP_DISCUSSION, $block);
            $block->onCommentCreated($posting);
        }
    }

    // store the blubber
    private function afterLecturerDiscussionBlubber($posting, $username)
    {
        $this->storeBlubber($posting, self::LECTURER_DISCUSSION, $username);
    }

    // store already seen blubbers
    private static $blubber_seen = array();

    const LECTURER_DISCUSSION = 'LecturerDiscussion';
    const GROUP_DISCUSSION    = 'GroupDiscussion';

    // store seen blubbers
    private function storeBlubber($blubber, $type, $data)
    {
        self::$blubber_seen[$blubber->id] = compact('blubber', 'type', 'data');
    }

    // try to find the Block matching the BlubberPosting
    private function getBlockFromGroupDiscussionBlubber($block_id)
    {
        // and that block must exist
        if (!$db_block = \Mooc\DB\Block::find($block_id)) {
            return null;
        }

        $container = \PluginEngine::getPlugin('Courseware')->getContainer();
        return $container['block_factory']->makeBlock($db_block);
    }

    // try to find the BlubberPosting matching the
    // PersonalNotification, then rewrite the URL of it
    function beforePersonalNotifications($event, $personal_notification)
    {
        $result = $this->findBlubberFromPersonalNotification($personal_notification);

        // nothing found, this is no courseware personal notification
        if (!$result) {
            return;
        }

        switch ($result['type']) {

        case self::LECTURER_DISCUSSION:
            $this->redirectLecturerDiscussionPN($personal_notification, $result['blubber']);
            break;

        case self::GROUP_DISCUSSION:
            $this->redirectGroupDiscussionPN($personal_notification, $result['blubber'], $result['data']);
            break;
        }
    }

    // find the BlubberPosting matching the PersonalNotification
    private function findBlubberFromPersonalNotification($personal_notification)
    {
        if (!preg_match('/posting_([0-9a-f]{32})/A', $personal_notification->html_id, $matches)) {
            return null;
        }

        $posting_id = $matches[1];
        if (!isset(self::$blubber_seen[$posting_id])) {
            return null;
        }

        return self::$blubber_seen[$posting_id];
    }

    // rewrite the URL of the LecturerDiscussion's PN
    private function redirectLecturerDiscussionPN($personal_notification, $posting)
    {
        $personal_notification->url = \PluginEngine::getURL(
            'Courseware',
            array(),
            sprintf('progress?uid=%s#%s',
                    $posting['user_id'],
                    $posting->root_id
            ));
    }

    // rewrite the URL of the GroupDiscussion's PN
    private function redirectGroupDiscussionPN($personal_notification, $posting, $block)
    {
        $personal_notification->url = \PluginEngine::getURL(
            'Courseware',
            array(),
            sprintf('courseware?selected=%s#%s',
                    $block->id,
                    $posting->root_id
            ));
    }

    // blubber's name must contain block_id of a GroupDiscussion block
    // or the unique block ID
    private function isGroupDiscussion($posting)
    {
        if (!preg_match('/Re: ([0-9]+)-(null|[0-9a-f]{32})/A', $posting->name, $matches)) {
            return null;
        }
        return (int) $matches[1];
    }

    // blubber's name must contain "user-{username}" to be a
    // LecturerDiscussion
    private function isLecturerDiscussion($posting)
    {
        if (!preg_match('/Re: user-([a-zA-Z0-9_@.-]{4,})/A', $posting->name, $matches)) {
            return null;
        }
        return $matches[1];
    }
}
