<?php

class BlocksController extends CoursewareStudipController {

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
        $ui_block = $this->plugin->getBlockFactory()->makeBlock($block);

        if ($this->acceptsJSON() || !$ui_block) {
            $json = $block->toArray();


            // FIXME: braucht irgendwer die children? Ich hab noch
            // keine Stelle gefunden.
            $json['children'] = array();
            foreach ($block->children as $child) {
                // TODO: Was genau braucht man von den Kindern?
                $json['children'][] = get_class($child);
            }

            // TODO: mlunzena: Warum sollten nicht-UI-Blöcke keine
            // Fields oder Grades haben?
            if ($ui_block) {
                $json['fields'] = $ui_block->getFields();
                $json['grade'] = $ui_block->getProgress()->grade;
            }

            $this->render_json($json);
        }

        // wants HTML
        else {
            $this->callBlockView($ui_block, $this->getViewParam(), clone Request::getInstance());
        }
    }


    function post($id)
    {
        // we need the handler and the data
        if (!isset($this->data['handler']) || !isset($this->data['data'])) {
            return $this->json_error('Requires handler and data.', 400);
        }

        // JSON requests only
        if (!$this->isJSONRequest()) {
            return $this->json_error('Only JSON requests.', 400);
        }

        $block = $this->requireBlock($id);
        $ui_block = $this->plugin->getBlockFactory()->makeBlock($block);

        if (!$ui_block) {
            return $this->json_error('No such handler.', 400);
        }

        $this->callBlockHandler($ui_block, $this->data['handler'], $this->data['data']);
    }


    function put($id)
    {
        // JSON requests only
        if (!$this->isJSONRequest()) {
            $this->json_error('Only JSON requests accepted.');
            return;
        }

        // TODO: title only at the moment. complete this!
        if (!isset($this->data['title'])) {
            $this->json_error('Title required.');
            return;
        }

        $title = trim($this->data['title']);
        if (!strlen($title)) {
            $this->json_error('Title must not be empty.');
            return;
        }

        $block = $this->requireBlock($id);

        if (!$this->plugin->getCurrentUser()->canUpdate($block)) {
            $this->json_error('Access Denied', 401);
            return;
        }

        $block->title = $title;
        $block->publication_date = (int)$this->data['publication_date'] ?: null;

        if (is_integer($block->store())) {
            $this->render_json($block->toArray());
        }

        else {
            $this->json_error('Could not modify block.');
        }
    }


    function delete($id)
    {
        // JSON requests only
        if (!$this->acceptsJSON()) {
            $this->json_error('Only JSON requests accepted.');
            return;
        }

        $block = $this->requireBlock($id);

        if (!$this->plugin->getCurrentUser()->canDelete($block)) {
            $this->json_error('Access Denied', 401);
            return;
        }

        $block->delete();

        $this->render_json(array('status' => 'ok'));
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

        // FIXME: Ist es nötig cid und block->seminar_id zu überprüfen?
        if ($block->seminar_id === $this->plugin->getCourseId()) {
            // häh?
        }

        if (!$this->plugin->getCurrentUser()->canRead($block)) {
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

    private function callBlockView($ui_block, $view, $context)
    {
        try {
            $this->render_html($ui_block->render($view, $context));
        }
        catch (\Mooc\UI\Errors\AccessDenied $ade) {
            $this->html_error($ade->getMessage(), 401);
        }
        catch (\Mooc\UI\Errors\BadRequest $bre) {
            $this->html_error($bre->getMessage(), 400);
        }
        catch (\Mooc\UI\Errors\NotFound $nfe) {
            $this->html_error('Not Found', 404);
        }
        catch (Exception $e) {
            $this->html_error($e->getMessage());
        }
    }

    private function callBlockHandler($ui_block, $handler, $data)
    {
        try {
            $json = $ui_block->handle($handler, $data);
            $this->render_json($json);
        }
        catch (\Mooc\UI\Errors\AccessDenied $ade) {
            $this->json_error('Access Denied', 401);
        }
        catch (\Mooc\UI\Errors\BadRequest $bre) {
            $this->json_error($bre->getMessage(), 400);
        }
        catch (\Mooc\UI\Errors\NotFound $nfe) {
            $this->json_error('Not Found', 404);
        }
        catch (Exception $e) {
            $this->json_error($e->getMessage());
        }
    }
}
