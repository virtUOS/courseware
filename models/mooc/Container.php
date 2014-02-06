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

        $this->setupEnv();
        $this->setupBlockStuff();
    }

    private function setupEnv()
    {
        $this['current_user_id'] = isset($GLOBALS['user']) ? $GLOBALS['user']->id : 'nobody';
    }

    private function setupBlockStuff()
    {
        $this['block_factory'] = function ($c) {
            return new \Mooc\UI\BlockFactory($c);
        };

        $this['block_renderer'] = function ($c) {
            return new \Mooc\MustacheRenderer();
        };
    }
}
