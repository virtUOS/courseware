<?php

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/models/mooc/constants.php';

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
        $this->setupAutoload();
        $this->setupContainer();
        $this->setupNavigation();

        // deactivate Vips-Plugin for students if this course is capture by the mooc-plugin
        if ($this->isSlotModule() && !$GLOBALS['perm']->have_studip_perm("tutor", $this->container['cid'])) {
            Navigation::removeItem('/course/vipsplugin');
        }
    }

    // bei Aufruf des Plugins über plugin.php/mooc/...
    public function initialize ()
    {
        PageLayout::addStylesheet($this->getPluginURL().'/assets/style.css');
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

    private function setupContainer()
    {
        $this->container = new Mooc\Container($this);
    }

    private function setupNavigation()
    {
        $moocid = Request::option('moocid');

        $url_overview = PluginEngine::getURL($this, array(), 'courses/overview', true);
        $url_courses = PluginEngine::getURL($this, array(), 'courses/index', true);

        $navigation = new Navigation('MOOCs', $url_overview);
        $navigation->setImage($GLOBALS['ABSOLUTE_URI_STUDIP'] . $this->getPluginPath() . '/assets/images/mooc.png');

        if (Request::get('moocid')) {
            $overview_url = PluginEngine::getURL($this, compact('moocid'), 'courses/show/' . $moocid, true);;
            $overview_subnav = new Navigation(_('Übersicht'), $overview_url);
            $overview_subnav->setImage(Assets::image_path('icons/16/white/seminar.png'));
            $overview_subnav->setActiveImage(Assets::image_path('icons/16/black/seminar.png'));
            $navigation->addSubnavigation("overview", $overview_subnav);

            $navigation->addSubnavigation('registrations', $this->getRegistrationsNavigation());
        } else {
            $navigation->addSubnavigation("overview", new Navigation(_('MOOCs'), $url_overview));
            $navigation->addSubnavigation("all", new Navigation(_('Alle Kurse'), $url_courses));
        }

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
        if (!$this->getSemClass()) {
            return false;
        }

        return $this->getSemClass()->isSlotModule(get_class($this));
    }


    private function getOverviewNavigation()
    {
        $cid = $this->getContext();
        $url = PluginEngine::getURL($this, compact('cid'), 'courses/show/' . $cid, true);

        $navigation = new Navigation(_('Übersicht'), $url);
        $navigation->setImage(Assets::image_path('icons/16/white/seminar.png'));
        $navigation->setActiveImage(Assets::image_path('icons/16/black/seminar.png'));

        $course = Course::find($cid);
        $sem_class = self::getMoocSemClass();

        $navigation->addSubNavigation('overview', new Navigation(_('Übersicht'), $url));

        if ($this->container['current_user']->hasPerm($cid, 'admin')
                && !$sem_class['studygroup_mode']
                && ($sem_class->getSlotModule("admin"))) {
            $navigation->addSubNavigation('admin', new Navigation(_('Administration dieser Veranstaltung'), 'adminarea_start.php?new_sem=TRUE'));
        }

        if (!$course->admission_binding && !$this->container['current_user']->hasPerm($cid, 'tutor')
                && $this->container['current_user_id'] != 'nobody') {
            $navigation->addSubNavigation('leave', new Navigation(_('Austragen aus der Veranstaltung'), 
                    'meine_seminare.php?auswahl='. $cid .'&cmd=suppose_to_kill'));
        }

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
        $navigation->addSubnavigation('index', clone $navigation);

        // TODO: should only be shown to students
        if (TRUE) {
            $progress_url = PluginEngine::getURL($this, compact('cid'), 'progress', true);
            $progress_subnav = new Navigation(_('Fortschrittsübersicht'), $progress_url);
            $navigation->addSubnavigation("progress", $progress_subnav);
        }

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
