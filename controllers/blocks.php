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

        if ($this->isJSONRequest() || !$ui_block) {
            $this->render_json($block->toArray());
        }

        // wants HTML
        else {
            $html = $this->container['block_renderer']($ui_block, 'student');
            $this->render_text($html);
        }
    }


    function post($id)
    {
        // we need the handler
        if (!isset($this->data['handler'])) {
            throw new Trails_Exception(400);
        }

        $handler = $this->data['handler'];
        unset($this->data['handler']);

        // JSON requests only
        if (!$this->isJSONRequest()) {
            throw new Trails_Exception(400);
        }

        $block = $this->requireBlock($id);
        $ui_block = $this->container['block_factory']->makeBlock($block);

        $json = $ui_block ? $ui_block->handle($handler, $this->data) : $block->toArray();

        $this->render_json($json);
    }


    /*****************************/
    /* PROTECTED & PRIVATE STUFF */
    /*****************************/

    function requireBlock($id)
    {
        $block = \Mooc\AbstractBlock::find($id);
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
}
