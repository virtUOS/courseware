<?php
namespace Mooc\UI;

/**
 * @author  <mlunzena@uos.de>
 */
class MustacheRenderer
{

    function __construct($container)
    {
        $this->container = $container;
    }

    public function __invoke(Block $ui_block, $view_name, $template_data) {
        $block_template_dir = $ui_block->getBlockDir() . '/templates/';
        $loader = new \Mustache_Loader_FilesystemLoader($block_template_dir);
        $m = new \Mustache_Engine(array(
            'cache'            => $GLOBALS['TMP_PATH'] . DIRECTORY_SEPARATOR . 'courseware',
            'charset'          => 'UTF-8',
            'escape'           => function($value) { return htmlspecialchars("$value", ENT_QUOTES, 'UTF-8', false); },
            'helpers'          => $this->container['block_renderer_helpers'],
            'loader'           => $loader,
            'strict_callables' => true
        ));

        return $m->render($view_name . '_view', $template_data);
    }
}
