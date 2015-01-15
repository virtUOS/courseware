<?php

class CoursewareController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        if (Navigation::hasItem('/course/mooc_courseware')) {
            Navigation::activateItem("/course/mooc_courseware");
        }

        $this->view = $this->getViewParam();

        // setup `context` parameter
        $this->context = clone Request::getInstance();

        $courseware = $this->container['courseware_factory']->makeCourseware($this->container['cid']);
        $this->courseware_block = $this->container['block_factory']->makeBlock($courseware);

        // add Templates
        $this->templates = $this->getMustacheTemplates();

        // add CSS
        $this->addBlockStyles();

    }


    private function getMustacheTemplates()
    {
        $templates = array();

        foreach (glob($this->plugin->getPluginPath() . '/blocks/*/templates/*.mustache') as $file) {
            preg_match('|blocks/([^/]+)/templates/([^/]+).mustache$|', $file, $matches);

            list(, $block, $name) = $matches;

            if (!isset($templates[$block])) {
                $templates[$block] = array();
            }

            $content = file_get_contents($file);

            $templates[$block][$name] = $content;
        }

        return $templates;
    }

    private function addBlockStyles()
    {
        return PageLayout::addStylesheet($GLOBALS['ABSOLUTE_URI_STUDIP'] . $this->plugin->getPluginPath() . '/assets/moocip.min.css');
    }
}
