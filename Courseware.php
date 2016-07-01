<?php

require_once __DIR__.'/vendor/autoload.php';

use Courseware\Container;
use Mooc\DB\CoursewareFactory;
use Mooc\UI\BlockFactory;
use Courseware\User;

function _cw($message) {
    return dgettext('courseware', $message);
}
/**
 * MoocIP.class.php
 *
 * ...
 *
 * @author  <tgloeggl@uos.de>
 * @author  <mlunzena@uos.de>
 */
class Courseware extends StudIPPlugin implements StandardPlugin, HomepagePlugin
{
    /**
     * @var Container
     */
    private $container;

    public function __construct() {
        parent::__construct();

        $this->setupAutoload();
        $this->setupContainer();

        // more setup if this plugin is active in this course
        if ($this->isActivated($this->container['cid'])) {

            // markup for link element to courseware
            StudipFormat::addStudipMarkup('courseware', '\[(mooc-forumblock):([0-9]{1,32})\]', NULL, 'Courseware::markupForumLink');

            $this->setupNavigation();
        }
        // set text-domain for translations in this plugin
        bindtextdomain('courseware', dirname(__FILE__) . '/locale');
    }

    public function getPluginname()
    {
        return 'MOOC.IP - Courseware';
    }

    // bei Aufruf des Plugins über plugin.php/mooc/...
    public function initialize ()
    {
        PageLayout::setTitle($_SESSION['SessSemName']['header_line'] . ' - ' . $this->getPluginname());
        PageLayout::addStylesheet($this->getPluginURL().'/assets/style.css');
        PageLayout::setHelpKeyword("MoocIP.Courseware"); // Hilfeseite im Hilfewiki
        $this->getHelpbarContent();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabNavigation($course_id)
    {
        $cid = $course_id;
        $tabs = array();

        $courseware = $this->container['current_courseware'];

        $navigation = new Navigation($courseware->title,
                                     PluginEngine::getURL($this, compact('cid'), 'courseware', true));
        $navigation->setImage('icons/16/white/group3.png');
        $navigation->setActiveImage('icons/16/black/group3.png');

        $tabs['mooc_courseware'] = $navigation;

        $navigation->addSubnavigation('index',    clone $navigation);
        $navigation->addSubnavigation('settings',
                                      new Navigation(_cw("Einstellungen"),
                                                     PluginEngine::getURL($this, compact('cid'), 'courseware/settings', true)));


        $progress_url = PluginEngine::getURL($this, compact('cid'), 'progress', true);

        // tabs for students
        if (!$this->container['current_user']->hasPerm($course_id, 'tutor')) {
            $progress_url = PluginEngine::getURL($this, compact('cid'), 'progress', true);
            $tabs['mooc_progress'] = new Navigation(_cw('Fortschrittsübersicht'), $progress_url);
            $tabs['mooc_progress']->setImage('icons/16/white/group3.png');
            $tabs['mooc_progress']->setActiveImage('icons/16/black/group3.png');
        }
        // tabs for tutors and up
        else {
            $discussions_url = PluginEngine::getURL($this, compact('cid'), 'courseware/discussions', true);
            $tabs['mooc_progress'] = new Navigation(_cw('Fortschrittsübersicht'), $progress_url);
            $tabs['mooc_discussions'] = new Navigation(_cw('Kommunikation'), $discussions_url);
            $survey_url = PluginEngine::getURL($this, compact('cid'), 'survey', true);
            $tabs['mooc_survey'] = new Navigation(_('Umfrage'), $survey_url);
        }

        return $tabs;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getIconNavigation($course_id, $last_visit, $user_id)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfoTemplate($course_id)
    {
        return null;
    }

    // homepageplugin template method
    public function getHomepageTemplate($user_id)
    {

        $user = $this->container['current_user'];

        // show contentbox only when visiting one's own page
        if ($user_id === $user->id) {

            // get all the courses with Courseware plugin activation
            $pid = $this->getPluginId();
            $pm  = \PluginManager::getInstance();
            $my_courses = $this->container['current_user']->course_memberships->filter(function ($cm) use ($pid, $pm) {
                    return $pm->isPluginActivated($pid, $cm->seminar_id);
                });

            // cannot show any discussions
            if (!sizeof($my_courses)) {
                return null;
            }

            $discussions = array();
            foreach ($my_courses as $my_course) {
                if ($my_course->status === 'autor') {
                    $discussions[] = new \Mooc\UI\DiscussionBlock\LecturerDiscussion($my_course->seminar_id, $user);
                }
            }

            $templatefactory = new Flexi_TemplateFactory(__DIR__ . '/views');
            $template = $templatefactory->open("profile/show.php");

            $template->discussions = $discussions;
            $template->plugin      = $this;
            $template->container   = $this->container;

            PageLayout::addStylesheet($this->getPluginURL().'/assets/mooc-profile.css');

            return $template;
        } else {
            return null;
        }
    }

    public function perform($unconsumed_path)
    {
        if (!$this->isActivated($this->container['cid'])) {
            throw new AccessDeniedException('plugin not activated for this course!');
        }

        require_once 'vendor/trails/trails.php';
        require_once 'app/controllers/studip_controller.php';
        require_once 'app/controllers/authenticated_controller.php';

        // load i18n only if plugin is un use
        PageLayout::addHeadElement('script', array(),
            "String.toLocaleString('".PluginEngine::getLink($this, array('cid' => null), "localization") ."');");

        $dispatcher = new Trails_Dispatcher(
            $this->getPluginPath(),
            rtrim(PluginEngine::getLink($this, array(), null), '/'),
            NULL
        );
        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return Request::option('cid') ?: $GLOBALS['SessionSeminar'];
    }

    /**
     * @return string
     */
    public function getCourseId()
    {
        return $this->container['cid'];
    }

    /**
     * @return CoursewareFactory
     */
    public function getCoursewareFactory()
    {
        return $this->container['courseware_factory'];
    }

    /**
     * @return BlockFactory
     */
    public function getBlockFactory()
    {
        return $this->container['block_factory'];
    }

    /**
     * @return string
     */
    public function getCurrentUserId()
    {
        return $this->container['current_user_id'];
    }

    /**
     * @return User
     */
    public function getCurrentUser()
    {
        return $this->container['current_user'];
    }

    /**
     * @return Pimple
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**********************************************************************/
    /* PRIVATE METHODS                                                    */
    /**********************************************************************/

    /*
     * Hilfeinhalte
     */
    protected function getHelpbarContent()
    {

        $can_edit = $GLOBALS['perm']->have_studip_perm("tutor", $this->container['cid']);

        if ($can_edit) {
            //$description = _cw("Mit dem Courseware-Modul können Sie interaktive Lernmodule in Stud.IP erstellen. Strukturieren Sie Ihre Inhalte in Kapiteln und Unterkapiteln. Schalten Sie zwischen Teilnehmenden-Sicht und Editier-Modus um und fügen Sie Abschnitte und Blöcke (Text und Bild, Video, Diskussion, Quiz)  hinzu. Aufgaben erstellen und verwalten Sie mit dem Vips-Plugin und binden Sie dann in einen Courseware-Abschnitt ein.");
            //Helpbar::get()->addPlainText(_cw('Information'), $description, 'icons/16/white/info-circle.png');

            //$tip = _cw("Sie können den Courseware-Reiter umbenennen! Wählen Sie dazu den Punkt 'Einstellungen', den Sie im Editiermodus unter der Seitennavigation finden.");
            //Helpbar::get()->addPlainText(_cw('Tipp'), $tip, 'icons/16/white/info-circle.png');
        } else {
            //$description = _cw("Über dieses Modul stellen Ihre Lehrenden Ihnen multimediale Lernmodule direkt in Stud.IP zur Verfügung. Die Module können Texte, Bilder, Videos, Kommunikationselemente und kleine Quizzes beinhalten. Ihren Bearbeitungsfortschritt sehen Sie auf einen Blick im Reiter Fortschrittsübersicht.");
            //Helpbar::get()->addPlainText(_cw('Hinweis'), $description, 'icons/16/white/info-circle.png');
        }
    }

    // setup Pimple container (only once!)
    private function setupContainer()
    {
        static $container;

        if (!$container) {
            $container = new Courseware\Container($this);
        }

        $this->container = $container;
    }

    // autoload the models (only once!)
    private function setupAutoload()
    {
        static $once;

        if (!$once) {
            $once = true;
            StudipAutoloader::addAutoloadPath(__DIR__ . '/models');
        }
    }

    private function setupNavigation()
    {
        // deactivate Vips-Plugin for students if this course is capture by the mooc-plugin
        if (!$GLOBALS['perm']->have_studip_perm("tutor", $this->container['cid'])) {
            if (Navigation::hasItem('/course/vipsplugin')){
                Navigation::removeItem('/course/vipsplugin');
            }
        }

        // FIXME: hier den Courseware-Block in die Hand zu bekommen,
        //        ist definitiv falsch.
        $courseware = $this->container['current_courseware'];

        // hide blubber tab if the discussion block is active
        if ($courseware->getDiscussionBlockActivation()) {
        Navigation::removeItem('/course/blubberforum');
    }
    }
    private function getSemClass()
    {
        global $SEM_CLASS, $SEM_TYPE, $SessSemName;
        return $SEM_CLASS[$SEM_TYPE[$SessSemName['art_num']]['class']];
    }

    /* * * * * * * * * * * * * * * * * * * * * * * *
     * * * * *   F O R U M   M A R K U P   * * * * *
     * * * * * * * * * * * * * * * * * * * * * * * */

    static function markupForumLink($markup, $matches, $contents)
    {
        // create a widget for given id (md5 hash - ensured by markup regex)
        return '<span class="mooc-forumblock">'
            . '<a href="'. PluginEngine::getLink('courseware' , array('selected' => $matches[2]), 'courseware') .'">'
            . _cw('Zurück zur Courseware')
            . '</a></span>';
    }
}
