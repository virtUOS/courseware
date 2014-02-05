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

    // TODO replace me soon
    public function test_action($block_id, $handler = null)
    {
        $sorm_block = \Mooc\Block::find($block_id);

        $factory = new \Mooc\UI\BlockFactory();
        $ui_block = $factory->makeBlock($sorm_block);

        echo $ui_block->render('student');

        if (isset($handler)) {
            echo $ui_block->handle($handler);
        }

        $this->render_nothing();
    }
}
