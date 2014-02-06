<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class MustacheRenderer
{
    public function __invoke($ui_block, $view_name) {
        $block_template_dir = $ui_block->getBlockDir() . '/templates/';
        $loader = new \Mustache_Loader_FilesystemLoader($block_template_dir);
        $m = new \Mustache_Engine(compact('loader'));

        $template_data = $ui_block->render($view_name);
        return $m->render($view_name . '_view', $template_data);
    }
}
