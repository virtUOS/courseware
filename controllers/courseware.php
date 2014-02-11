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

        $this->courseware = \Mooc\Courseware::findByCourse($this->container['cid']);

        // TODO: verify section is in courseware & we have access to it
        // TODO: Section::find finds any block not only Sections. Ouch!


        // TODO
        $this->section = current(
            \Mooc\AbstractBlock::findBySQL(
                'seminar_id = ? AND type = "Section" ORDER BY parent_id, position',
                array($this->container['cid'])));

        $this->selected   = $this->section->id;
        $this->subchapter = $this->section->subchapter;
        $this->chapter    = $this->subchapter->chapter;
    }

    // TODO replace me soon
    public function test_action($block_id, $handler = null)
    {
        $sorm_block = \Mooc\Block::find($block_id);
        $ui_block = $this->container['block_factory']->makeBlock($sorm_block);

        $renderer = $this->container['block_renderer'];
        echo $renderer($ui_block, 'student');

        if (isset($handler)) {
            echo $ui_block->handle($handler);
        }

        $this->render_nothing();
    }
}
