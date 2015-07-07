<?php

class CoursewareController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->courseware_block = $this->container['current_courseware'];
    }

    // default action; just shows the complete courseware at the
    // selected block's page
    public function index_action()
    {
        if (Navigation::hasItem('/course/mooc_courseware/index')) {
            Navigation::activateItem('/course/mooc_courseware/index');
        }

        $this->view = $this->getViewParam();

        // setup `context` parameter
        $this->context = clone Request::getInstance();

        // add Templates
        $this->templates = $this->getMustacheTemplates();

        // add CSS
        $this->addBlockStyles();
    }


    // show this course's settings page
    public function settings_action()
    {
        if (Navigation::hasItem('/course/mooc_courseware/settings')) {
            Navigation::activateItem('/course/mooc_courseware/settings');
        }

        if (!$GLOBALS['perm']->have_studip_perm('tutor', $this->plugin->getCourseId())) {
            throw new Trails_Exception(401);
        }

        if (Request::isPost()) {
            CSRFProtection::verifyUnsafeRequest();
            $this->storeSettings();

            $this->flash['success'] = _("Die Einstellungen wurden gespeichert.");
            return $this->redirect('courseware/settings');
        }
    }


    /////////////////////
    // PRIVATE HELPERS //
    /////////////////////

    // concat all the mustache templates
    // TODO: shouldn't this be cached somehow?
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

    // include the stylesheets of all default block types
    private function addBlockStyles()
    {
        return PageLayout::addStylesheet(
            $GLOBALS['ABSOLUTE_URI_STUDIP'] .
            $this->plugin->getPluginPath() .
            '/assets/courseware.min.css');
    }

    // validate and store sent settings
    private function storeSettings()
    {
        $courseware_settings = Request::getArray('courseware');

        //////////////////////
        // COURSEWARE TITLE //
        //////////////////////
        if (isset($courseware_settings['title'])) {
            $this->storeCoursewareTitle($courseware_settings['title']);
        }


        ////////////////////////////
        // COURSEWARE PROGRESSION //
        ////////////////////////////
        if (isset($courseware_settings['progression'])) {
            $this->storeCoursewareProgressionType($courseware_settings['progression']);
        }

        /////////////////////////////////
        // DISCUSSION BLOCK ACTIVATION //
        /////////////////////////////////
        $this->storeDiscussionBlockActivation(isset($courseware_settings['discussionblock_activation']) ? true : false);

        $this->courseware_block->save();
    }

    private function storeCoursewareTitle($title0)
    {
        $title = trim($title0);

        if (strlen($title)) {
            $this->courseware_block->title = $title;
        } else {
            // TODO: send a message back
        }
    }

    private function storeCoursewareProgressionType($type)
    {
        if (!$this->courseware_block->setProgressionType($type)) {
            // TODO: send a message back
        }
    }

    private function storeDiscussionBlockActivation($active)
    {
        if (!$this->courseware_block->setDiscussionBlockActivation($active)) {
            // TODO: send a message back
        }
    }
}
