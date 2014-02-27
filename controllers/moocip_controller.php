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

        $this->data = studip_utf8decode($this->parseRequestBody());

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


    protected function isJSONRequest()
    {
        $is_json = false;

        if ($contentType = $_SERVER['CONTENT_TYPE']) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            $mediaType = strtolower($contentTypeParts[0]);
            $is_json = $mediaType === 'application/json';
        }

        return $is_json;
    }

    private function parseRequestBody()
    {
        $input = file_get_contents('php://input');

        if ($this->isJSONRequest()) {
            return self::parseJson($input);
        }

        else {
            return self::parseFormEncoded($input);
        }

        return $input;
    }

    private static function parseJson($input)
    {
        return json_decode($input, true);
    }

    private static function parseFormEncoded($input)
    {
        parse_str($input, $result);
        return $result;
    }

    const ALLOWED_VIEWS = 'student author';

    public function getViewParam()
    {
        $view = Request::option('view', 'student');
        if (!in_array($view, words(self::ALLOWED_VIEWS))) {
            throw new Trails_Exception(400);
        }
        return $view;
    }
}
