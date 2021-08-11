<?php

/**
 * @property \Courseware $plugin
 */
class CoursewareStudipController extends StudipController
{
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
        $this->plugin    = $dispatcher->plugin;
        $this->container = $this->plugin->getContainer();
        $this->flash     = Trails_Flash::instance();
    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->data = $this->parseRequestBody();
        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
        $this->setDefaultPageTitle();
    }

    /**
     * overwrite the default url_for to enable it to work in plugins
     *
     * @param type $to
     * @return type
     */
    function url_for($to  = '')
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
     */
    function render_json($data)
    {
        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($data));
    }

    /**
     * Render Stud.IP specific HTML
     *
     */
    function render_html($html)
    {
        $this->response->add_header('Content-Type', 'text/html;charset=utf-8');
        $this->render_text($html);
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

    public function getViewParam()
    {
        return Request::option('view', 'student');
    }

    // predicat returning true if the client wants JSON
    public function acceptsJSON()
    {
        $negotiator   = new \Negotiation\FormatNegotiator();
        $acceptHeader = $_SERVER['HTTP_ACCEPT'];
        $priorities   = array('application/json', 'text/html');
        $format = $negotiator->getBest($acceptHeader, $priorities);

        return $format && $format->getValue() === $priorities[0];
    }

    // display a JSON error
    protected function json_error($reason, $status = 500, $data = null)
    {
        $this->response->set_status($status);
        $payload = array(
            'status' => 'error',
            'reason' => $reason
        );

        if (isset($data)) {
            $payload['data'] = (array) $data;
        }

        $this->render_json($payload);
    }

    // display an HTML error
    protected function html_error($reason, $status = 500)
    {
        $this->response->set_status($status);
        $this->render_html(MessageBox::error($reason));
    }

    private function setDefaultPageTitle()
    {
        $courseware = $this->container['current_courseware'];
        $header_line = class_exists('Context') ? Context::getHeaderLine() : $_SESSION['SessSemName']['header_line'];
        \PageLayout::setTitle($header_line . ' - ' . $courseware->title);
    }
}
