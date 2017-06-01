<?php

require_once __DIR__.'/vendor/autoload.php';

use Courseware\Container;
use Mooc\DB\CoursewareFactory;
use Mooc\UI\BlockFactory;
use Courseware\User;
use Mooc\UI\TestBlock\Vips\Bridge as VipsBridge;

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
 * @author  <rlucke@uos.de>
 */
class Courseware extends StudIPPlugin implements StandardPlugin
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
        return 'Courseware';
    }

    // bei Aufruf des Plugins über plugin.php/mooc/...
    public function initialize ()
    {
        //PageLayout::setTitle($_SESSION['SessSemName']['header_line'] . ' - ' . $this->getPluginname());
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
        $navigation->setImage(Icon::create('group3', 'info_alt'));
        $navigation->setActiveImage(Icon::create('group3', 'info'));
        $tabs['mooc_courseware'] = $navigation;

        $navigation->addSubnavigation('index',    clone $navigation);
        $navigation->addSubnavigation('settings',
                                      new Navigation(_cw("Einstellungen"),
                                                     PluginEngine::getURL($this, compact('cid'), 'courseware/settings', true)));

        // tabs for students
        if (!$this->container['current_user']->hasPerm($course_id, 'tutor')) {
            $progress_url = PluginEngine::getURL($this, compact('cid'), 'progress', true);
            $tabs['mooc_progress'] = new Navigation(_cw('Fortschrittsübersicht'), $progress_url);
            $tabs['mooc_progress']->setImage(Icon::create('assessment', 'info_alt'));
            $tabs['mooc_progress']->setActiveImage(Icon::create('assessment', 'info'));
        }
        // tabs for tutors and up
        else {
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
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        $icon = new AutoNavigation($this->getDisplayTitle(), PluginEngine::getURL($this, compact('cid'), 'courseware', true));
        $img_path = $this->getPluginURL(). '/assets/images/';
        
        $db = DBManager::get();
        $stmt = $db->prepare("
            SELECT
                COUNT(*)
            FROM
                mooc_blocks
            WHERE
                seminar_id = :cid
            AND
                chdate >= :last_visit
        ");
        $stmt->bindParam(":cid", $course_id);
        $stmt->bindParam(":last_visit", $last_visit);
        $stmt->execute();
        $new_ones =  (int) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];

        if(VipsBridge::vipsExists()) {
            // getting all tests
            $db = DBManager::get();
            $stmt = $db->prepare("
                SELECT 
                    json_data 
                FROM 
                    mooc_blocks
                JOIN 
                    mooc_fields 
                ON 
                    mooc_blocks.id = mooc_fields.block_id 
                WHERE 
                    mooc_blocks.type = 'TestBlock'
                AND
                    mooc_blocks.seminar_id = :cid
                AND 
                    mooc_fields.name = 'test_id' 
            ");
            $stmt->bindParam(":cid", $course_id);
            $stmt->execute();
            
            $tests =  $stmt->fetch(PDO::FETCH_ASSOC);
            if($tests) {
                $test_ids = array();
                foreach ($tests as $key=>$value){
                        array_push($test_ids, (int) str_replace('"', '', $value));
                }
                //looking for new tests
                $stmt = $db->prepare("
                    SELECT
                        COUNT(*)
                    FROM
                        vips_exercise_ref
                    JOIN
                        vips_aufgabe
                    ON
                        vips_exercise_ref.exercise_id = vips_aufgabe.ID
                    WHERE
                        vips_exercise_ref.test_id IN (".implode(', ', $test_ids).")
                    AND
                        unix_timestamp(created) >=  :last_visit
                ");
                $stmt->bindParam(":last_visit", $last_visit);
                $stmt->execute();
                $new_ones +=  (int) $stmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
            }
        }
        if ($new_ones) {
            $title = $new_ones > 1 ? sprintf(_("%s neue Courseware-Inhalte"), $new_ones) : _("1 neuer Courseware-Inhalt");
            $icon->setImage(Icon::create('group3', 'attention', ["title" => $title]));
            $icon->setBadgeNumber($new_ones);
        } else {
            $icon->setImage(Icon::create('group3', 'inactive', ["title" => "Courseware"]));
        }
        return $icon;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfoTemplate($course_id)
    {
        return null;
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

    static function onEnable($id)
    {
        $db = DBManager::get();

        // find plugin activations of old Mooc.IP plugin and duplicate them for the new courseware plugin
        $plugin_id     = (int)$db->query("SELECT pluginid FROM plugins
            WHERE pluginclassname = 'Mooc'")->fetchColumn();

        if ($plugin_id) {
            $db->exec("INSERT INTO plugins_activated
                SELECT $id as pluginid, poiid, state FROM plugins_activated WHERE pluginid = $plugin_id");
        }
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
            $description = _cw("Mit dem Courseware-Modul können Sie interaktive Lernmodule in Stud.IP erstellen. Strukturieren Sie Ihre Inhalte in Kapiteln und Unterkapiteln. Schalten Sie zwischen Teilnehmenden-Sicht und Editier-Modus um und fügen Sie Abschnitte und Blöcke (Text und Bild, Video, Diskussion, Quiz)  hinzu. Aufgaben erstellen und verwalten Sie mit dem Vips-Plugin und binden Sie dann in einen Courseware-Abschnitt ein.");
            Helpbar::get()->addPlainText(_cw('Information'), $description, 'icons/white/info-circle.svg');

            $tip = _cw("Sie können den Courseware-Reiter umbenennen! Wählen Sie dazu den Punkt 'Einstellungen', den Sie im Editiermodus unter der Seitennavigation finden.");
            Helpbar::get()->addPlainText(_cw('Tipp'), $tip, 'icons/white/info-circle.svg');
        } else {
            $description = _cw("Über dieses Modul stellen Ihre Lehrenden Ihnen multimediale Lernmodule direkt in Stud.IP zur Verfügung. Die Module können Texte, Bilder, Videos, Kommunikationselemente und kleine Quizzes beinhalten. Ihren Bearbeitungsfortschritt sehen Sie auf einen Blick im Reiter Fortschrittsübersicht.");
            Helpbar::get()->addPlainText(_cw('Hinweis'), $description, 'icons/white/info-circle.svg');
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
        // FIXME: hier den Courseware-Block in die Hand zu bekommen,
        //        ist definitiv falsch.
        $courseware = $this->container['current_courseware'];

        // hide blubber tab if the discussion block is active
        if ($courseware->getDiscussionBlockActivation()) {
            Navigation::removeItem('/course/blubberforum');
        }

        // deactivate Vips-Plugin for students if this course is capture by the mooc-plugin
        if ( (!$GLOBALS['perm']->have_studip_perm("tutor", $this->container['cid'])) && $courseware->getVipsTabVisible() ) {
            if (Navigation::hasItem('/course/vipsplugin')){
                Navigation::removeItem('/course/vipsplugin');
            }
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
    
    public function getDisplayTitle() {
        return _("Courseware");
    }
}
