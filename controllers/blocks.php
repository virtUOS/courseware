<?php

require_once 'moocip_controller.php';

class BlocksController extends MoocipController {

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (sizeof($args) !== 1 || !strlen($args[0])) {
            throw new Trails_Exception(400);
        }
    }

    function get($id)
    {
        $block = $this->requireBlock($id);
        $ui_block = $this->container['block_factory']->makeBlock($block);

        if ($this->acceptsJSON() || !$ui_block) {
            $json = $block->toArray();


            $json['children'] = array();
            foreach ($block->children as $child) {
                // TODO: Was genau braucht man von den Kindern?
                $json['children'][] = get_class($child);
            }

            // TODO: mlunzena: Warum sollten nicht-UI-Blöcke keine
            // Fields haben?
            if ($ui_block) {
                $json['fields'] = $ui_block->getFields();
            }

            $this->render_json($json);
        }

        // wants HTML
        else {

            $view = $this->getViewParam();
            $context = clone Request::getInstance();

            $this->response->add_header('Content-Type', 'text/html;charset=windows-1252');
            $this->render_text($ui_block->render($view, $context));
        }
    }


    function post($id)
    {
        // we need the handler and the data
        if (!isset($this->data['handler']) || !isset($this->data['data'])) {
            throw new Trails_Exception(400);
        }

        // JSON requests only
        if (!$this->isJSONRequest()) {
            throw new Trails_Exception(400);
        }

        $handler = $this->data['handler'];
        $data = $this->data['data'];

        $block = $this->requireBlock($id);
        $ui_block = $this->container['block_factory']->makeBlock($block);

        $json = $ui_block ? $ui_block->handle($handler, $data) : $block->toArray();

        $this->render_json($json);
    }


    /*****************************/
    /* PROTECTED & PRIVATE STUFF */
    /*****************************/

    function requireBlock($id)
    {
        $block = \Mooc\DB\Block::find($id);
        if (!isset($block)) {
            throw new Trails_Exception(404);
        }

        if ($block->seminar_id === $this->container['cid']) {
            // häh?
        }

        if (!$this->container['current_user']->canRead($block)) {
            throw new Trails_Exception(401);
        }

        return $block;
    }


    /**
     * Extracts action and args from a string.
     *
     * @param  string       the processed string
     *
     * @return array        an array with two elements - a string containing the
     *                      action and an array of strings representing the args
     */
    function extract_action_and_args($string) {
        return array($this->get_verb(), explode('/', $string));
    }


    function map_action($action) {
        return array(&$this, strtolower($action));
    }


    function get_verb() {

        $verb = strtoupper(isset($_REQUEST['_method'])
        ? $_REQUEST['_method'] : @$_SERVER['REQUEST_METHOD']);

        if (!preg_match('/^DELETE|GET|POST|PUT|HEAD|OPTIONS$/', $verb)) {
            throw new Trails_Exception(405);
        }

        return $verb;
    }

    function acceptsJSON() {
        $negotiator   = new \Negotiation\FormatNegotiator();

        $acceptHeader = $_SERVER['HTTP_ACCEPT'];
        $priorities   = array('application/json');

        $format = $negotiator->getBest($acceptHeader, $priorities);
        return $format && $format->getValue() === $priorities[0];
    }
}
