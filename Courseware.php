<?php

require_once __DIR__.'/vendor/autoload.php';

use Courseware\Container;
use Mooc\DB\CoursewareFactory;
use Mooc\UI\BlockFactory;
use Courseware\User;

function _cw($message)
{
    return dgettext('courseware', $message);
}

/**
 * MoocIP.class.php.
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

    public function __construct()
    {
        parent::__construct();

        $this->setupAutoload();
        $this->setupContainer();

        // more setup if this plugin is active in this course
        if ($this->isActivated($this->container['cid'])) {
            // markup for link element to courseware
            StudipFormat::addStudipMarkup('courseware', '\[(mooc-forumblock):([0-9]{1,32})\]', null, 'Courseware::markupForumLink');

            $this->setupNavigation();
        }

        // set text-domain for translations in this plugin
        bindtextdomain('courseware', dirname(__FILE__).'/locale');
    }

    public function getPluginname()
    {
        return 'Courseware';
    }

    // bei Aufruf des Plugins über plugin.php/mooc/...
    public function initialize()
    {
        //PageLayout::setTitle($_SESSION['SessSemName']['header_line'] . ' - ' . $this->getPluginname());
        // setTitle -> courseware_controller.php
        PageLayout::setHelpKeyword('MoocIP.Courseware'); // Hilfeseite im Hilfewiki
        $this->getHelpbarContent();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabNavigation($courseId)
    {
        $cid = $courseId;
        $tabs = array();

        $courseware = $this->container['current_courseware'];

        $navigation = new Navigation(
            $courseware->title,
            PluginEngine::getURL($this, compact('cid'), 'courseware', true)
        );
        $navigation->setImage(Icon::create('group3', 'info_alt'));
        $navigation->setActiveImage(Icon::create('group3', 'info'));
        $tabs['mooc_courseware'] = $navigation;

        $navigation->addSubnavigation('index', clone $navigation);

        $navigation->addSubnavigation(
            'news',
            new Navigation(
                _cw('Letzte Änderungen'),
                PluginEngine::getURL($this, compact('cid'), 'courseware/news', true)
            )
        );

        //NavigationForLecturers
        if ($this->container['current_user']->hasPerm($courseId, 'tutor')) {
            $settingsUrl = PluginEngine::getURL($this, compact('cid'), 'courseware/settings', true);
            $navigation->addSubnavigation(
                'settings', 
                new Navigation(_cw('Einstellungen'), $settingsUrl)
            );

            $cpoUrl = PluginEngine::getURL($this, compact('cid'), 'cpo', true);
            $navigation->addSubnavigation(
                'progressoverview',
                new Navigation(_cw('Fortschrittsübersicht'), $cpoUrl)
            );

            $postoverviewUrl = PluginEngine::getURL($this, compact('cid'), 'cpo/postoverview', true);
            $navigation->addSubnavigation(
                'postoverview',
                new Navigation(_cw('Diskussionsübersicht'), $postoverviewUrl)
            );

            $exportUrl = PluginEngine::getURL($this, compact('cid'), 'export', true);
            $navigation->addSubnavigation(
                'export',
                new Navigation(_cw('Export'), $exportUrl)
            );

            $importUrl = PluginEngine::getURL($this, compact('cid'), 'import', true);
            $navigation->addSubnavigation(
                'import',
                new Navigation(_cw('Import'), $importUrl)
            );

        //NavigationForStudents
        } else {
            if (!$this->container['current_user']->isNobody()) {
                $progressUrl = PluginEngine::getURL($this, compact('cid'), 'progress', true);
                $navigation->addSubnavigation(
                    'progress',
                    new Navigation(_cw('Fortschrittsübersicht'), $progressUrl)
                );
            }
        }

        return $tabs;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationObjects($courseId, $since, $user_id)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getIconNavigation($courseId, $last_visit, $user_id)
    {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }
        $icon = new AutoNavigation(
            $this->getDisplayTitle(),
            PluginEngine::getURL($this, array('cid' => $courseId, 'iconnav' => 'true'), 'courseware/news', true)
        );

        $db = DBManager::get();

        $stmt = $db->prepare('
            SELECT
                COUNT(*)
            FROM
                mooc_blocks
            WHERE
                seminar_id = :cid
            AND
                chdate >= :last_visit
        ');
        $stmt->bindParam(':cid', $courseId);
        $stmt->bindParam(':last_visit', $last_visit);
        $stmt->execute();
        $new_ones = (int) $stmt->fetch(PDO::FETCH_ASSOC)['COUNT(*)'];

        $plugin_manager = \PluginManager::getInstance();
        $vips = true;
        if ($plugin_manager->getPluginInfo('VipsPlugin') == null){
            $vips = false;
        }
        if($plugin_manager->getPlugin('VipsPlugin')){ 
            $version = $plugin_manager->getPluginManifest($plugin_manager->getPlugin('VipsPlugin')->getPluginPath())['version'];
            if (version_compare('1.3',$version) > 0) {
                $vips = false;
            }
        } else {
            $vips = false;
        }

        if ($vips) {
            // getting all tests
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
            $stmt->bindParam(':cid', $courseId);
            $stmt->execute();

            $tests = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($tests) {
                $test_ids = array();
                foreach ($tests as $key => $value) {
                    array_push($test_ids, (int) str_replace('"', '', $value));
                }
                //looking for new tests
                $stmt = $db->prepare('
                    SELECT
                        COUNT(*)
                    FROM
                        vips_exercise_ref
                    JOIN
                        vips_exercise
                    ON
                        vips_exercise_ref.exercise_id = vips_exercise.ID
                    WHERE
                        vips_exercise_ref.test_id IN ('.implode(', ', $test_ids).')
                    AND
                        unix_timestamp(created) >=  :last_visit
                ');
                $stmt->bindParam(':last_visit', $last_visit);
                $stmt->execute();
                $new_ones += (int) $stmt->fetch(PDO::FETCH_ASSOC)['COUNT(*)'];
            }
        }
        if ($new_ones) {
            $title = $new_ones > 1 ? sprintf(_('%s neue Courseware-Inhalte'), $new_ones) : _('1 neuer Courseware-Inhalt');
            $icon->setImage(Icon::create('group3', 'attention', ['title' => $title]));
            $icon->setBadgeNumber($new_ones);
        } else {
            $icon->setImage(Icon::create('group3', 'inactive', ['title' => 'Courseware']));
        }

        return $icon;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfoTemplate($courseId)
    {
        return null;
    }

    public function perform($unconsumedPath)
    {
        if ((!$this->isActivated($this->container['cid']))&& ($_SERVER['REQUEST_METHOD'] === 'GET')) {
            throw new AccessDeniedException('plugin not activated for this course!');
        }

        require_once 'vendor/trails/trails.php';
        require_once 'app/controllers/studip_controller.php';
        require_once 'app/controllers/authenticated_controller.php';

        // load i18n only if plugin is un use
        PageLayout::addHeadElement('script', array(),
            "String.toLocaleString('".PluginEngine::getLink($this, array('cid' => $this->container['cid']), 'localization')."');");

        $dispatcher = new Trails_Dispatcher(
            $this->getPluginPath(),
            rtrim(PluginEngine::getLink($this, array(), null), '/'),
            null
        );
        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumedPath);
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $GLOBALS['SessionSeminar'];
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

    public static function onEnable($id)
    {
        $dbm = DBManager::get();

        // find plugin activations of old Mooc.IP plugin and duplicate them for the new courseware plugin
        $pluginId = (int) $dbm->query("SELECT pluginid FROM plugins WHERE pluginclassname = 'Mooc'")->fetchColumn();

        if ($pluginId) {
            $dbm->exec("INSERT INTO plugins_activated
                SELECT $id as pluginid, poiid, state FROM plugins_activated WHERE pluginid = $pluginId");
        }

        // enable nobody role by default
        RolePersistence::assignPluginRoles($id, array(7));
    }

    /**********************************************************************/
    /* PRIVATE METHODS                                                    */
    /**********************************************************************/

    /*
     * Hilfeinhalte
     */
    protected function getHelpbarContent()
    {
        $can_edit = $GLOBALS['perm']->have_studip_perm('tutor', $this->container['cid']);

        if ($can_edit) {
            $description = _cw('Mit dem Courseware-Modul können Sie interaktive Lernmodule in Stud.IP erstellen. Strukturieren Sie Ihre Inhalte in Kapiteln und Unterkapiteln. Schalten Sie zwischen Teilnehmenden-Sicht und Editier-Modus um und fügen Sie Abschnitte und Blöcke (Text und Bild, Video, Diskussion, Quiz)  hinzu. Aufgaben erstellen und verwalten Sie mit dem Vips-Plugin und binden Sie dann in einen Courseware-Abschnitt ein.');
            Helpbar::get()->addPlainText(_cw('Information'), $description, 'icons/white/info-circle.svg');

            $tip = _cw("Sie können den Courseware-Reiter umbenennen! Wählen Sie dazu den Punkt 'Einstellungen', den Sie im Editiermodus unter der Seitennavigation finden.");
            Helpbar::get()->addPlainText(_cw('Tipp'), $tip, 'icons/white/info-circle.svg');
        } else {
            $description = _cw('Über dieses Modul stellen Ihre Lehrenden Ihnen multimediale Lernmodule direkt in Stud.IP zur Verfügung. Die Module können Texte, Bilder, Videos, Kommunikationselemente und kleine Quizzes beinhalten. Ihren Bearbeitungsfortschritt sehen Sie auf einen Blick im Reiter Fortschrittsübersicht.');
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
            StudipAutoloader::addAutoloadPath(__DIR__.'/models');
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
        if ((!$GLOBALS['perm']->have_studip_perm('tutor', $this->container['cid'])) && $courseware->getVipsTabVisible()) {
            if (Navigation::hasItem('/course/vipsplugin')) {
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

    public static function markupForumLink($markup, $matches, $contents)
    {
        // create a widget for given id (md5 hash - ensured by markup regex)
        return '<span class="mooc-forumblock">'
            .'<a href="'.PluginEngine::getLink('courseware', array('selected' => $matches[2]), 'courseware').'">'
            ._cw('Zurück zur Courseware')
            .'</a></span>';
    }

    public function getDisplayTitle()
    {
        return _('Courseware');
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * *
     * * * * Functions for DatenschutzPlugin * * * * *
     * * * * * * * * * * * * * * * * * * * * * * * * */

    /*
     * Returns the tables containing user data.
     * the array consists of the tables containing user data
     * the expected format for each table is:
     * $array[ table display name ] = [ 'table_name' => name of the table, 'table_content' => array of db rows containing userdata]
     * @param string $user_id
     * @return array
     */
    public static function getUserdataInformation($user_id)
    {
        $db = DBManager::get();

        $stmt = $db->prepare('
            SELECT
                *
            FROM
                mooc_userprogress
            WHERE
                user_id = :uid
        ');
        $stmt->bindParam(':uid', $user_id);
        $stmt->execute();
        $user_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $plugin_data = array();
        $plugin_data['Courseware - Nutzerfortschritt'] = ['table_name' => 'mooc_userprogress', 'table_content' => $user_progress];

        $stmt = $db->prepare('
            SELECT
                *
            FROM
                mooc_fields
            WHERE
                user_id = :uid
        ');
        $stmt->bindParam(':uid', $user_id);
        $stmt->execute();
        $block_content = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $plugin_data['Courseware - Blöcke'] = ['table_name' => 'mooc_userprogress', 'table_content' => $block_content];

        return $plugin_data;
    }

    /**
     * Returns the filerefs of given user.
     * @param string $user_id
     * @return array
     */
    public static function getUserFileRefs($user_id)
    {
        return array();
    }

    /**
     * Deletes the table content containing user data.
     * @param string $user_id
     * @return boolean
     */
    public static function deleteUserdata($user_id)
    {
        $db = DBManager::get();
        $exec = false;

        $stmt = $db->prepare('
            DELETE FROM
                mooc_userprogress 
            WHERE 
                user_id = :uid
        ');
        $stmt->bindParam(':uid', $user_id);
        $exec = $stmt->execute();

        $stmt = $db->prepare('
            DELETE FROM
                mooc_fields 
            WHERE 
                user_id = :uid
            AND 
                (name = "visited" OR name = "lastSelected")
        ');
        $stmt->bindParam(':uid', $user_id);
        $exec = $stmt->execute();

        return $exec;
    }

}
