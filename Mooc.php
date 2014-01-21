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

        $this->setupNavigation();
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
        return array();
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
        if (class_exists("StudipAutoloader")) {
            StudipAutoloader::addAutoloadPath(__DIR__ . '/models');
        } else {
            spl_autoload_register(function ($class) {
                include_once __DIR__ . $class . '.php';
            });
        }
    }

    private function setupNavigation()
    {
        global $perm;

        $cid = $this->getContext();
        if (Request::isXhr()
            || Navigation::hasItem('/course/mooc')
            || !$this->isActivated($cid))
        {
            return;
        }

        $url = PluginEngine::getURL($this, compact('cid'), 'TODO', true);

        $navigation = new Navigation(_('Mooc'), $url);
        $navigation->setImage(Assets::image_path('icons/16/white/category.png'));
        $navigation->setActiveImage(Assets::image_path('icons/16/black/category.png'));

        Navigation::addItem('/course/mooc', $navigation);
    }

    private function getContext()
    {
        return Request::option("cid");
    }
}
