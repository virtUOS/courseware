<?php
class MoocipController extends StudipController {

    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin = $dispatcher->plugin;
        $this->container = $this->plugin->container;
        $this->flash = Trails_Flash::instance();
    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
    }

    /**
     * overwrite the default url_for to enable it to work in plugins
     *
     * @param type $to
     * @return type
     */
    function url_for($to)
    {
        $args = func_get_args();

        // find params
        $params = array();
        if (is_array(end($args))) {
            $params = array_pop($args);
        }

        // urlencode all but the first argument
        $args = array_map('urlencode', $args);
        $args[0] = $to;

        return PluginEngine::getURL($this->dispatcher->plugin, $params, join('/', $args));
    }


    /**
     * Render some data as JSON.
     *
     * @param Mixed $data  some WINDOWS-1252 encoded data
     */
    function render_json($data)
    {
        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode(studip_utf8encode($data)));
    }
}
