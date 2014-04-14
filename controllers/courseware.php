<?php

class CoursewareController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        Navigation::activateItem("/course/mooc_courseware");

        $this->view = $this->getViewParam();

        // setup `context` parameter
        $this->context = clone Request::getInstance();

        $cid = $this->container['cid'];
        $this->courseware = $this->container['courseware_factory']->makeCourseware($cid);
        $this->courseware_block = $this->container['block_factory']->makeBlock($this->courseware);

        // TODO: verify section is in courseware & we have access to it
        // TODO: Section::find finds any block not only Sections. Ouch!

        // TODO
        $this->section = current(
            \Mooc\DB\Block::findBySQL(
                'seminar_id = ? AND type = "Section" ORDER BY parent_id, position',
                array($cid)));

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
        foreach (glob($this->plugin->getPluginPath() . '/blocks/*/css/*.css') as $file) {
            if (substr(basename($file), 0, 1) === '_') {
                continue;
            }
            PageLayout::addStylesheet($GLOBALS['ABSOLUTE_URI_STUDIP'] . $file);
        }
    }
}
