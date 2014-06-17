<?php

class ProgressController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        Navigation::activateItem("/course/mooc_courseware/progress");

        $cid    = $this->container['cid'];
        $uid    = $this->container['current_user_id'];

        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position', array($cid));
        $bids   = array_map(function ($block) { return (int) $block->id; }, $blocks);
        $progress = array_reduce(
            \Mooc\DB\UserProgress::findBySQL('block_id IN (?) AND user_id = ?', array($bids, $uid)),
            function ($memo, $item) {
                $memo[$item->block_id] = $item->grade / $item->max_grade;
                return $memo;
            },
            array());

        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position', array($cid)),
            function($memo, $item) {
                $memo[$item->parent_id][] = $item->toArray();
                return $memo;
            },
            array());

        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $progress, $this->courseware);
    }

    private function buildTree($grouped, $progress, &$root)
    {
        $this->addChildren($grouped, $root);

        if ($root['type'] !== 'Section') {
            foreach($root['children'] as &$child) {
                $this->buildTree($grouped, $progress, $child);
            }
            $this->computeProgress($root);
        }

        else {
            $root['children'] = $this->addChildren($grouped, $root);
            if ($root['children']) {
                $grades = array_map(
                    function ($block) use ($progress) { return (double) $progress[$block['id']]; },
                    $root['children']);
                $root['progress'] = array_sum($grades) / sizeof($grades);
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
}
