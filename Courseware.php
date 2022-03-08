<?php

require_once __DIR__.'/vendor/autoload.php';

use CoursewarePlugin\Container;
use Mooc\DB\CoursewareFactory;
use Mooc\UI\BlockFactory;
use CoursewarePlugin\User;


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
    }

    public function getPluginname()
    {
        return 'Migration alter Courseware-Inhalte';
    }

    // bei Aufruf des Plugins über plugin.php/mooc/...
    public function initialize()
    {
        PageLayout::setHelpKeyword('Basis/Courseware'); // Hilfeseite im Hilfewiki
        $this->getHelpbarContent();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabNavigation($cid)
    {
        $navigation = new Navigation(
            $this->getPluginname(),
            PluginEngine::getURL($this, compact('cid'), 'courseware', true)
        );
        $navigation->addSubnavigation('index', clone $navigation);

        return array('mooc_courseware' => $navigation);
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
        return false;
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
        $description = _cw('Courseware ist nun fester Bestandteil von Stud.IP. Bitte Wählen Sie den Menüpunkt "Courseware" um die neue Courseware aufzurufen.');
        Helpbar::get()->addPlainText(_cw('Hinweis'), $description, 'icons/white/info.svg');
    }

    // setup Pimple container (only once!)
    private function setupContainer()
    {
        static $container;

        if (!$container) {
            $container = new CoursewarePlugin\Container($this);
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

    private function getSemClass()
    {
        global $SEM_CLASS, $SEM_TYPE, $SessSemName;

        return $SEM_CLASS[$SEM_TYPE[$SessSemName['art_num']]['class']];
    }

    /* * * * * * * * * * * * * * * * * * * * * * * *
     * * * * *   F O R U M   M A R K U P   * * * * *
     * * * * * * * * * * * * * * * * * * * * * * * */


    public function getDisplayTitle()
    {
        return _('Courseware');
    }

    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $metadata['pluginname'] = dgettext('Courseware', 'Courseware');
        $metadata['displayname'] = dgettext('Courseware', 'Courseware');
        $metadata['summary'] = dgettext('Courseware', 'Create and provide interactive multimedia learning modules');
        $metadata['description'] = dgettext('Courseware', 'With courseware you can create and use interactive multimedia learning modules. The modules are structured into chapters, subsections and sections and can consist of text blocks, video sequences, tasks (requires the Vips plug-in) and communication elements. Modules can be exported and imported into other courses or other installations.');
        $metadata['descriptionShort'] = dgettext('Courseware', 'Create and provide interactive multimedia learning modules');
        $metadata['descriptionLong'] = dgettext('Courseware', 'With courseware you can create and use interactive multimedia learning modules. The modules are structured into chapters, subsections and sections and can consist of text blocks, video sequences, tasks (requires the Vips plug-in) and communication elements. Modules can be exported and imported into other courses or other installations.');
        $metadata['homepage'] = dgettext('Courseware', 'https://hilfe.studip.de/help/4.0/en/Basis/Courseware');

        return $metadata;
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
