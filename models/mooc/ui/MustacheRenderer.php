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
            'loader'  => $loader,
            'charset' => 'windows-1252',
            'helpers' => $this->container['block_renderer_helpers']
        ));

        return $m->render($view_name . '_view', $template_data);
    }
}
