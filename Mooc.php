<?php

require_once 'models/mooc/constants.php';

/**
 * MoocIP.class.php
 *
 * ...
 *
 * @author  <tgloeggl@uos.de>
 * @author  <mlunzena@uos.de>
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

    private function setupNavigation()
    {
        $moocid = Request::option('moocid');

        $url = PluginEngine::getURL($this, array(), 'courses/index', true);

        $navigation = new Navigation('MOOCs', $url);
        $navigation->setImage(Assets::image_path('icons/32/white/category.png'));

        $overview_url = PluginEngine::getURL($this, compact('moocid'), 'courses/show/' . $moocid, true);;
        $overview_subnav = new Navigation("Übersicht", $overview_url);
        $overview_subnav->setImage(Assets::image_path('icons/16/white/seminar.png'));
        $overview_subnav->setActiveImage(Assets::image_path('icons/16/black/seminar.png'));
        $navigation->addSubnavigation("overview", $overview_subnav);

        #if ($GLOBALS['user']->id == 'nobody') {
            $navigation->addSubnavigation('registrations', $this->getRegistrationsNavigation());
        #}

        Navigation::addItem('/mooc', $navigation);
    }

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
        $cid = $this->getContext();
        $url = PluginEngine::getURL($this, compact('cid'), 'courses/show/' . $cid, true);

        $navigation = new Navigation('Übersicht', $url);
        $navigation->setImage(Assets::image_path('icons/16/white/seminar.png'));
        $navigation->setActiveImage(Assets::image_path('icons/16/black/seminar.png'));

        return $navigation;
    }

    private function getRegistrationsNavigation()
    {
        $moocid = Request::option('moocid');
        $url = PluginEngine::getURL($this, compact('moocid'), 'registrations/new', true);

        $navigation = new Navigation('Anmeldung', $url);
        $navigation->setImage(Assets::image_path('icons/16/white/door-enter.png'));
        $navigation->setActiveImage(Assets::image_path('icons/16/black/door-enter.png'));

        return $navigation;
    }

    private function getCoursewareNavigation()
    {
        $cid = $this->getContext();
        $url = PluginEngine::getURL($this, compact('cid'), 'courseware', true);

        $navigation = new Navigation('Courseware', $url);
        $navigation->setImage(Assets::image_path('icons/16/white/category.png'));
        $navigation->setActiveImage(Assets::image_path('icons/16/black/category.png'));
        return $navigation;
    }

    static function onEnable($id)
    {
        // enable nobody role by default
        RolePersistence::assignPluginRoles($id, array(7));

        self::insertMoocIntoOverviewSlot();
    }

    static function onDisable($id)
    {
        self::removeMoocFromOverviewSlot();
    }

    const OVERVIEW_SLOT = 'overview';

    private static function insertMoocIntoOverviewSlot()
    {
        $sem_class = self::getMoocSemClass();
        $sem_class->setSlotModule(self::OVERVIEW_SLOT, __CLASS__);
        $sem_class->store();
    }

    private static function removeMoocFromOverviewSlot()
    {
        $sem_class = self::getMoocSemClass();
        $default_module = SemClass::getDefaultSemClass()->getSlotModule(self::OVERVIEW_SLOT);
        $sem_class->setSlotModule(self::OVERVIEW_SLOT, $default_module);
        $sem_class->store();
    }

    private static function getMoocSemClass()
    {
        return new SemClass(
            intval(self::getMoocSemClassID()));
    }

    private static function getMoocSemClassID()
    {
        $id = Config::get()->getValue(\Mooc\SEM_CLASS_CONFIG_ID);
        return $id;
    }
}
