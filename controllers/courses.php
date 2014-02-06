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
        // get rid of the currently selected course
        closeObject();

        Navigation::activateItem('/mooc/all');

        $sem_class = \Mooc\SemClass::getMoocSemClass();
        $this->courses = $sem_class->getCourses();
    }

    public function show_action($cid)
    {
        if (strlen($cid) !== 32) {
            throw new Trails_Exception(400);
        }

        if ($GLOBALS['SessionSeminar']) {
            Navigation::activateItem("/course/mooc_overview");
        } else {
            Navigation::activateItem("/mooc/overview");
        }

        $this->courseware = \Mooc\Courseware::findByCourse($cid);
        $this->course = Course::find($cid);
    }
}
