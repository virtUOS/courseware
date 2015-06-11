<?php

use Mooc\UI\DiscussionBlock\LecturerDiscussion;

class ProgressController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        PageLayout::addStylesheet($GLOBALS['ABSOLUTE_URI_STUDIP'] . $this->plugin->getPluginPath() . '/assets/courseware.min.css');

    }

    public function index_action()
    {
        if (Navigation::hasItem('/course/mooc_progress')) {
            Navigation::activateItem("/course/mooc_progress");
        }

        $this->mode = $GLOBALS['perm']->have_studip_perm("tutor", $this->plugin->getCourseId()) ? 'total' : 'single';
        $this->members = CourseMember::findByCourseAndStatus($this->plugin->getCourseId(), 'autor');

        $uid = NULL;
        $this->current_user = NULL; // expose current user to template
        if ($this->mode == 'total') { // list all participants
            if (Request::option('uid')) { // one participant selected
                foreach ($this->members as $m) {
                    if ($m->user_id == Request::option('uid')) {
                        $uid = $m->user_id;
                        $this->current_user = $m->user;
                    }
                }
            } else if ($this->members) {  // no one selected: take first, if there are participants at all
                $m = $this->members[0];
                $uid = $m->user_id;
                $this->current_user = $m->user;
            }
        } else { // single mode: only show my results
            $uid = $this->plugin->getCurrentUserId();
            $this->current_user = $this->container['current_user'];
        }

        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position', array($this->plugin->getCourseId()));
        $bids   = array_map(function ($block) { return (int) $block->id; }, $blocks);
        $progress = array_reduce(
            \Mooc\DB\UserProgress::findBySQL('block_id IN (?) AND user_id = ?', array($bids, $uid)),
            function ($memo, $item) {
                $memo[$item->block_id] = array(
                    'grade' => $item->grade,
                    'max_grade' => $item->max_grade,
                );

                return $memo;
            },
            array());

        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position', array($this->plugin->getCourseId())),
            function($memo, $item) {
                $memo[$item->parent_id][] = $item->toArray();
                return $memo;
            },
            array());

        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $progress, $this->courseware);


        // show discussions
        $this->discussion = isset($this->current_user) ? new LecturerDiscussion($this->container['cid'], $this->current_user) : null;
    }

    private function buildTree($grouped, $progress, &$root)
    {
        $this->addChildren($grouped, $root);

        if ($root['type'] !== 'Section') {
            foreach($root['children'] as &$child) {
                $this->buildTree($grouped, $progress, $child);
            }
            $root['progress'] = $this->computeProgress($root);
        }

        else {
            $root['children'] = $this->addChildren($grouped, $root);
            if ($root['children']) {
                $grades = array_map(
                    function ($block) use ($progress) {
                        return (double) $progress[$block['id']]['grade'];
                    },
                    $root['children']
                );
                $maxGrades = array_map(
                    function ($block) use ($progress) {
                        return (double) $progress[$block['id']]['max_grade'];
                    },
                    $root['children']
                );

                if (array_sum($maxGrades) > 0) {
                    $root['progress'] = array_sum($grades) / array_sum($maxGrades);
                } else {
                    $root['progress'] = 0;
                }
            }
            else {
                $root['progress'] = 0;
            }
        }
    }


    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = array_filter(
            isset($grouped[$parent['id']]) ? $grouped[$parent['id']] : array(),
            function ($item) {
                return $item['publication_date'] <= time();
            });
        return $parent['children'];
    }

    private function computeProgress(&$block)
    {
        if (!sizeof($block['children'])) {
            return 0;
        }

        return
            array_sum(
                array_map(
                    function ($section) {return $section['progress']; },
                    $block['children'])
            ) / sizeof($block['children']);
    }

    // include the stylesheets of all default block types
    private function addBlockStyles()
    {
        return PageLayout::addStylesheet(
            $GLOBALS['ABSOLUTE_URI_STUDIP'] .
            $this->plugin->getPluginPath() .
            '/assets/courseware.min.css');
    }

}


