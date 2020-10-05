<?php

use Mooc\UI\Courseware\Courseware;
use Mooc\UI\TestBlock\Vips\Bridge as VipsBridge;

class CoursewareController extends CoursewareStudipController
{
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
    }

    // show this course's settings page but only to tutors+
    public function settings_action()
    {
        // only tutor+ may visit this page
        if (!$GLOBALS['perm']->have_studip_perm('tutor', $this->plugin->getCourseId())) {
            throw new Trails_Exception(401);
        }

        if (Navigation::hasItem('/course/mooc_courseware/settings')) {
            Navigation::activateItem('/course/mooc_courseware/settings');
        }

        $user = $this->container['current_user'];

        if (!$user->hasPerm($this->container['cid'], 'tutor')) {
            throw new Trails_Exception(401);
        }

        $this->is_tutor = $user->getPerm($this->container['cid']) === 'tutor';

        if (Request::isPost()) {
            CSRFProtection::verifyUnsafeRequest();
            $this->storeSettings();
            $this->flash['success'] = _cw("Die Einstellungen wurden gespeichert.");

            return $this->redirect('courseware/settings');
        }
    }

    public function news_action()
    {
        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css');
        //get all new blocks and push them into an array
        $db = DBManager::get();
        $stmt = $db->prepare("
            SELECT
                *
            FROM
                mooc_blocks
            WHERE
                seminar_id = :cid
            AND
                chdate >= :last_visit
            AND
				type NOT IN ('Courseware', 'Chapter', 'Subchapter', 'Section')
        ");
        $stmt->bindParam(":cid", $this->container['cid']);
        $stmt->bindParam(":last_visit", object_get_visit($this->container['cid'], "courseware"));
        $stmt->execute();
        $new_ones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->new_ones = $new_ones;

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
            $stmt->bindParam(":cid", $this->container['cid']);
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
                        *
                    FROM
                        vips_exercise_ref
                    JOIN
                        vips_exercise
                    ON
                        vips_exercise_ref.exercise_id = vips_exercise.ID
                    WHERE
                        vips_exercise_ref.test_id IN (".implode(', ', $test_ids).")
                    AND
                        unix_timestamp(created) >=  :last_visit
                ");
                $stmt->bindParam(":last_visit", object_get_visit($_SESSION['SessionSeminar'], "courseware"));
                $stmt->execute();
                $new_tests =  $stmt->fetchAll(PDO::FETCH_ASSOC);

                $this->new_ones = array_merge($this->new_ones, $new_tests);
            }
        }

        if (Navigation::hasItem('/course/mooc_courseware/news')) {
            Navigation::activateItem('/course/mooc_courseware/news');
        }
        if(empty($this->new_ones) && (Request::get("iconnav") == 'true')) {
            return $this->redirect('courseware');
        }
        $this->new_content = array();
        foreach ($this->new_ones as $item){
            $block = new Mooc\DB\Block($item["id"]);
            if ( (strpos($item["title"], "AsideSection") >-1) || (in_array($block->type , array("Chapter", "Subchapter", "Section"))) ){continue;}
            if($block->parent->parent->parent != null) {
                if (!$block->parent->parent->parent->isPublished() || !$block->parent->parent->parent->isVisible()) {continue;}
            }
            $chapter = $block->parent->parent->parent->title;
            if($block->parent->parent != null) {
                if (!$block->parent->parent->isPublished() || !$block->parent->parent->isVisible()) {continue;}
            }
            $subchapter = $block->parent->parent->title;
            $section = $block->parent->title;
            if (!$block->isVisible()) {continue;}
            $class_name = 'Mooc\UI\\'.$block->type.'\\'.$block->type;
            $name_constant = $class_name.'::NAME';

            if (defined($name_constant)) {
                $title = _cw(constant($name_constant));
            } else {
                $title = $block->title;
            }
            $ui_block = $this->plugin->getBlockFactory()->makeBlock($block);
            $this->new_content[$chapter][$subchapter][$section][$block->id] = array(
                'title' => $title,
                'type' => $block->type,
                'id' => $block->id,
                'ui_block' => $ui_block
            );
        }

        return true;
    }

    /////////////////////
    // PRIVATE HELPERS //
    /////////////////////

    // concat all the mustache templates
    // TODO: shouldn't this be cached somehow?
    private function getMustacheTemplates()
    {
        $templates = array();

        // add templates and load less files from block plugins
        $plugin_template_files = array();

        foreach (\Courseware::$registered_blocks as $block) {
            $plugin_template_files = array_merge(
                $plugin_template_files,
                glob($block['path'] . '/templates/*.mustache')
            );

            // add stylesheets for block to page
            foreach (glob($block['path'] . '/css/*.css') as $source) {
                PageLayout::addHeadElement('link', [
                    'rel'  => 'stylesheet',
                    'type' => 'text/css',
                    'href' => UrlHelper::getURL($source,  ['cid' => null])
                ]);
            }

            // add base js for block to page
            PageLayout::addHeadElement('script', [
                'src'  => URLHelper::getUrl($block['path'] .'/js/'. basename($block['path']) .'.js'),
                'type' => 'module'
            ], '');
        }

        // add base templates, integrating plugin templates
        foreach (array_merge(
            glob($this->plugin->getPluginPath() . '/blocks/*/templates/*.mustache'),
            $plugin_template_files
        ) as $file) {
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

        //////////////////////
        // VIPS TAB VISIBLE //
        //////////////////////
        $this->storeVipsTabVisible(isset($courseware_settings['vipstab_visible']) ? true : false);

        /////////////////////////
        // Sections Navigation //
        ////////////////////////
        switch ($courseware_settings['section_navigation']) {
            case "default":
                $this->storeShowSectionNav(true);
                $this->storeSectionsAsChatpers(false);
                break;
            case "chapter":
                $this->storeShowSectionNav(true);
                $this->storeSectionsAsChatpers(true);
                break;
            case "hide":
                $this->storeShowSectionNav(false);
                $this->storeSectionsAsChatpers(false);
                break;
        }

        /////////////////////////
        //   Scrollytelling   //
        ////////////////////////
        $this->storeScrollytelling(isset($courseware_settings['scrollytelling']) ? true : false);

        ////////////////////////
        // EDITING PERMISSION //
        ////////////////////////
        if (!$this->is_tutor) {
            $this->storeEditingPermission(isset($courseware_settings['editing_permission']) ? true : false);
        }

        /////////////////////////////
        // MAX COUNT FOR SELFTESTS //
        /////////////////////////////
        $try1 = isset($courseware_settings['max-tries-infinity']);
        $try2 = isset($courseware_settings['max-tries']);
        if (isset($courseware_settings['max-tries-infinity'])) {
            $this->storeMaxCount(-1);
        } else if(isset($courseware_settings['max-tries'])) {
            $this->storeMaxCount($courseware_settings['max-tries']);
        }

        ///////////////////////
        // MAX COUNT FOR IAV //
        ///////////////////////
        $try3 = isset($courseware_settings['max-tries-iav-infinity']);
        $try4 = isset($courseware_settings['max-tries-iav']);
        if (isset($courseware_settings['max-tries-iav-infinity'])) {
            $this->storeMaxCountIAV(-1);
        } else if(isset($courseware_settings['max-tries-iav'])) {
            $this->storeMaxCountIAV($courseware_settings['max-tries-iav']);
        }

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

    private function storeVipsTabVisible($active)
    {
        if (!$this->courseware_block->setVipsTabVisible($active)) {
            // TODO: send a message back
        }
    }

    private function storeShowSectionNav($active)
    {
        if (!$this->courseware_block->setShowSectionNav($active)) {
            // TODO: send a message back
        }
    }

    private function storeSectionsAsChatpers($active)
    {
        if (!$this->courseware_block->setSectionsAsChapters($active)) {
            // TODO: send a message back
        }
    }

    private function storeScrollytelling($active)
    {
        if (!$this->courseware_block->setScrollytelling($active)) {
            // TODO: send a message back
        }
    }

    private function storeEditingPermission($tutor_may_edit)
    {
        $perm = $tutor_may_edit
              ? Courseware::EDITING_PERMISSION_TUTOR
              : Courseware::EDITING_PERMISSION_DOZENT;

        // tutors may not edit the courseware, thus they may not edit
        // this setting
        if ($perm === Courseware::EDITING_PERMISSION_DOZENT &&
            $this->container['current_user']->getPerm($this->container['cid']) === 'tutor') {
            throw new Trails_Exception(401, _cw("Tutoren können diese Einstellung nicht speichern."));
        }

        if (!$this->courseware_block->setEditingPermission($perm)) {
            // TODO: send a message back
        }
    }

    private function storeMaxCount($count)
    {
        if(!$this->courseware_block->setMaxTries($count)) {
            // TODO: send a message back
        }
    }

    private function storeMaxCountIAV($count)
    {
        if(!$this->courseware_block->setMaxTriesIAV($count)) {
            // TODO: send a message back
        }
    }
}
