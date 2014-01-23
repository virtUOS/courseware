<?php

require_once 'moocip_controller.php';

class CoursewareController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->cid = $this->plugin->getContext();
    }

    public function index_action()
    {
        Navigation::activateItem("/course/mooc_courseware");
        $this->courseware = \Mooc\Courseware::findByCourse($this->cid);
    }
}
