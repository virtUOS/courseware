<?php

class CoursewareController extends CoursewareStudipController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        if (Navigation::hasItem('/course/mooc_courseware/index')) {
            Navigation::activateItem('/course/mooc_courseware/index');
        }

        $this->status = \Mooc\DB\MigrationStatus::findOneBySQL('seminar_id = ?', array($this->container['cid']));
        $this->CoursewareLink = URLHelper::getLink('dispatch.php/course/courseware/', ['cid' => $this->container['cid']]);
    }

    // show this course's settings page but only to tutors+
    public function settings_action()
    {
        return $this->redirect('courseware');
    }
    
    public function news_action()
    {
        return $this->redirect('courseware');

    }
}
