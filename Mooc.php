<?php

/**
 * MoocIP.class.php
 *
 * ...
 *
 * @author  virtuos
 * @version 0.1.0
 */

class Mooc extends StudIPPlugin implements StandardPlugin, SystemPlugin
{

    public function __construct() {
        parent::__construct();
    }

    // bei Aufruf des Plugins über plugin.php/mooc/...
    public function initialize ()
    {
        PageLayout::addStylesheet($this->getPluginURL().'/assets/style.css');
        PageLayout::addScript($this->getPluginURL().'/assets/application.js');
    }

    // für Veranstaltungskategorien-Slots
    public function getTabNavigation($course_id)
    {
        if ($this->isSlotModule()) {
            return array(
                'mooc_overview' => $this->getOverviewNavigation(),
                'mooc_courseware' => $this->getCoursewareNavigation()
            );
        }

        else {
            return array(
                'mooc_courseware' => $this->getCoursewareNavigation()
            );
        }
    }

    // ???
    public function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

    // Icon auf meine_seminare.php
    public function getIconNavigation($course_id, $last_visit, $user_id)
    {
        // ...
    }

    // Box auf der Übersichtsseite
    public function getInfoTemplate($course_id)
    {
        // ...
    }

    public function perform($unconsumed_path)
    {
        require_once 'vendor/trails/trails.php';
        require_once 'app/controllers/studip_controller.php';
        require_once 'app/controllers/authenticated_controller.php';

        $this->setupAutoload();
        $dispatcher = new Trails_Dispatcher(
            $this->getPluginPath(),
            rtrim(PluginEngine::getLink($this, array(), null), '/'),
            NULL
        );
        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }

    /**********************************************************************/
    /* PRIVATE METHODS                                                    */
    /**********************************************************************/

    private function setupAutoload()
    {
        StudipAutoloader::addAutoloadPath(__DIR__ . '/models');
    }

    public function getContext()
    {
        return Request::option('cid') ?: $GLOBALS['SessionSeminar'];
    }

    private function getSemClass()
    {
        global $SEM_CLASS, $SEM_TYPE, $SessSemName;
        return $SEM_CLASS[$SEM_TYPE[$SessSemName['art_num']]['class']];
    }

    private function isSlotModule()
    {
        return  $this->getSemClass()->isSlotModule(get_class($this));
    }


    private function getOverviewNavigation()
    {
        $url = PluginEngine::getURL($this, compact('cid'), 'course', true);

        $navigation = new Navigation('Übersicht', $url);
        $navigation->setImage(Assets::image_path('icons/16/white/seminar.png'));
        $navigation->setActiveImage(Assets::image_path('icons/16/black/seminar.png'));

        return $navigation;
    }

    private function getCoursewareNavigation()
    {
        $url = PluginEngine::getURL($this, compact('cid'), 'courseware', true);

        $navigation = new Navigation('Courseware', $url);
        $navigation->setImage(Assets::image_path('icons/16/white/category.png'));
        $navigation->setActiveImage(Assets::image_path('icons/16/black/category.png'));
        # TODO
        #            var_dump($this->getSemClass()->getNavigationForSlot("overview"));
        #            var_dump(Navigation::getItem('/course')->getSubNavigation());
        return $navigation;
    }
}
