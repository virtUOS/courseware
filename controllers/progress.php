<?php

class ProgressController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        Navigation::activateItem("/course/mooc_courseware/progress");


        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position', array($this->container['cid']));

        $this->grouped = array_reduce(
            $blocks,
            function($memo, $item) {
                $memo[$item->parent_id][] = $item;
                return $memo;
            },
            array());
    }
}
