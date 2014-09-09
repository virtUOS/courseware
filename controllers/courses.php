<?php

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
        $this->preview_images = array();

        foreach ($this->courses as $course) {
            $localEntries = DataFieldEntry::getDataFieldEntries($course->seminar_id);
            foreach ($localEntries as $entry) {
                if ($entry->structure->accessAllowed($GLOBALS['perm'])) {
                    if ($entry->getValue()) {
                        foreach ($this->container['datafields'] as $field => $id) {
                            if ($field != 'preview_image') {
                                continue;
                            }

                            if ($entry->getId() == $id) {
                                $this->preview_images[$course->id] = $entry->getValue();
                            }
                        }
                    }
                }
            }
        }
    }

    public function overview_action($edit = false)
    {
        Navigation::activateItem('/mooc/overview');

        $block = current(\Mooc\DB\Block::findBySQL('seminar_id IS NULL AND parent_id IS NULL'));
        
        if (!$block) {
            $block = \Mooc\DB\Block::create(array('type' => 'HtmlBlock', 'title' => 'LandingPage'));
        }
        
        $this->ui_block = $this->container['block_factory']->makeBlock($block);
        $this->context  = clone Request::getInstance();
        $this->view     = 'student';
        $this->root     = $this->container['current_user']->getPerm() == 'root';
        
        if ($edit && $this->root) {
            $this->view = 'author';
        }
    }
    
    function store_overview_action()
    {
        Navigation::activateItem('/mooc/overview');

        if ($this->container['current_user']->getPerm() != 'root') {
            throw new AccessDeniedException('You need to be root to edit the overview-page');
        }

        $block = current(\Mooc\DB\Block::findBySQL('seminar_id IS NULL AND parent_id IS NULL'));
        
        if (!$block) {
            $block = \Mooc\DB\Block::create(array('type' => 'HtmlBlock'));
        }
        
        $ui_block = $this->container['block_factory']->makeBlock($block);
        $ui_block->handle('save', array('content' => Request::get('content')));
        
        $this->redirect('courses/overview');
    }
    
    public function show_action($cid)
    {
        if (strlen($cid) !== 32) {
            throw new Trails_Exception(400);
        }

        if ($GLOBALS['SessionSeminar']) {
            Navigation::activateItem("/course/mooc_overview/overview");
        } else {
            Navigation::activateItem("/mooc/overview");
        }

        $this->courseware = \Mooc\DB\Block::findCourseware($cid);
        $this->course = Course::find($cid);
        $localEntries = DataFieldEntry::getDataFieldEntries($cid);
        foreach ($localEntries as $entry) {
            if ($entry->structure->accessAllowed($GLOBALS['perm'])) {
                if ($entry->getValue()) {
                    foreach ($this->container['datafields'] as $field => $id) {
                        if ($entry->getId() == $id) {
                            $this->$field = $entry->getValue();
                        }
                    }
                }
            }
        }
    }
}
