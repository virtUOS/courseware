<?php

require_once 'moocip_controller.php';

class CourseController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        Navigation::activateItem("/course/mooc_overview");
        $this->answer = 'yes';
    }
}
