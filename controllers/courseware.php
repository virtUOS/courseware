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

        $template_data = $ui_block->render('student');
        $block_template_dir = $factory->getBlockDir($sorm_block->type) . '/templates';

        require_once 'vendor/flexi/lib/mustache_template.php';

        $factory = new Flexi_TemplateFactory($block_template_dir);
        $factory->add_handler('mustache', 'Flexi_MustacheTemplate');
        var_dump($factory->render('student_view', $template_data));

        if (isset($handler)) {
            echo $ui_block->handle($handler);
        }

        $this->render_nothing();
    }
}
