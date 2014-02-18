<?php

require_once 'moocip_controller.php';

class CoursewareController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        Navigation::activateItem("/course/mooc_courseware");

        $this->courseware = \Mooc\DB\Block::findCourseware($this->container['cid']);
        $this->courseware_block = $this->container['block_factory']->makeBlock($this->courseware);

        // TODO: verify section is in courseware & we have access to it
        // TODO: Section::find finds any block not only Sections. Ouch!

        // TODO
        $this->section = current(
            \Mooc\DB\Block::findBySQL(
                'seminar_id = ? AND type = "Section" ORDER BY parent_id, position',
                array($this->container['cid'])));

        $this->active = $this->section->getAncestors();
    }
}
