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
        $sem_class = \Mooc\SemClass::getMoocSemClass();
        $this->courses = $sem_class->getCourses();
    }

    public function show_action()
    {
        Navigation::activateItem("/course/mooc_overview");

        $this->courseware = \Mooc\Courseware::findByCourse($this->cid);
    }
}
