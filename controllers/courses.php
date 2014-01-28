<?php

require_once 'moocip_controller.php';

class CoursesController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->cid = $this->plugin->getContext();
    }

    public function index_action()
    {
        $this->render_text(__METHOD__);
    }

    public function show_action()
    {
        Navigation::activateItem("/course/mooc_overview");

        $this->courseware = \Mooc\Courseware::findByCourse($this->cid);
    }
}
