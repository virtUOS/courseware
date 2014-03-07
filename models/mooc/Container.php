<?php
namespace Mooc;

/**
 * @author  <mlunzena@uos.de>
 */
class Container extends \Pimple
{
    public function __construct($plugin)
    {
        parent::__construct();

        $this['plugin'] = $plugin;

        $this->setupEnv();
        $this->setupBlockStuff();
        $this->setupCoursewareStuff();
    }

    private function setupEnv()
    {
        $this['current_user_id'] = isset($GLOBALS['user']) ? $GLOBALS['user']->id : 'nobody';

        $this['current_user'] = function ($c) {
            $user = new \Mooc\User($c, $c['current_user_id']);
            if ($user->isNew()) {
                // TODO: mlunzena: create a nobody user
            }
            return $user;
        };


        $this['cid'] = \Request::option('cid') ?: $GLOBALS['SessionSeminar'];
    }

    private function setupBlockStuff()
    {
        $this['block_factory'] = function ($c) {
            return new \Mooc\UI\BlockFactory($c);
        };

        $this['block_renderer'] = function ($c) {
            return new \Mooc\UI\MustacheRenderer($c);
        };

        $container = $this;
        $this['block_renderer_helpers'] = array(
            'i18n'       => function ($text) { return _($text); },
            'plugin_url' => function ($text, $helper) use ($container) {
                return \PluginEngine::getURL($container['plugin'], array(), $helper->render($text));
            }

        );
    }

    private function setupCoursewareStuff()
    {
        $this['courseware_factory'] = function ($c) {
            return new \Mooc\DB\CoursewareFactory($c);
        };
    }
}
