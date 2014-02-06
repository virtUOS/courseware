<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class Container extends \Pimple
{
    public function __construct()
    {
        parent::__construct();

        $this->setupBlockStuff();
    }

    private function setupBlockStuff()
    {
        $this['block_factory'] = function ($c) {
            return new \Mooc\UI\BlockFactory();
        };

        $this['block_renderer'] = function ($c) {
            return new \Mooc\MustacheRenderer();
        };
    }
}
