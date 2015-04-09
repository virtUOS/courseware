<?php

use Mooc\UI\Block;
use Mooc\UI\Courseware\Courseware;
use Mooc\UI\VideoBlock\VideoBlock;

/**
 * @property \Request   $context
 * @property Courseware $courseware
 * @property \Course[]  $courses
 * @property \Course    $course
 * @property Block      $ui_block
 * @property string     $view
 * @property bool       $root
 * @property string     $cid
 * @property string[]   $preview_images
 * @property string     $preview_image
 * @property string     $preview_video
 */
class CoursesController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->cid = $this->plugin->getContext();
    }

    public function index_action()
    {
        // get rid of the currently selected course
        closeObject();

        if (Navigation::hasItem('/mooc/all')) {
            Navigation::activateItem('/mooc/all');
        }

        $sem_class = \Mooc\SemClass::getMoocSemClass();
        $this->courses = $sem_class->getCourses();
        $this->preview_images = array();

        foreach ($this->courses as $course) {
            /** @var \DataFieldEntry[] $localEntries */
            $localEntries = DataFieldEntry::getDataFieldEntries($course->seminar_id);
            foreach ($localEntries as $entry) {
                /** @var \DataFieldStructure $structure */
                $structure = $entry->structure;
                if ($structure->accessAllowed($GLOBALS['perm'])) {
                    if ($entry->getValue()) {
                        foreach ($this->plugin->getDataFields() as $field => $id) {
                            if ($field != 'preview_image') {
                                continue;
                            }

                            if ($entry->getId() == $id) {
                                $this->preview_images[$course->id] = $entry->getValue();
                            }
                        }
                    }
                }
            }
        }
    }

    public function overview_action($edit = false)
    {
        if (Navigation::hasItem('/mooc/overview')) {
            Navigation::activateItem('/mooc/overview');
        }

        $block = current(\Mooc\DB\Block::findBySQL('seminar_id IS NULL AND parent_id IS NULL'));

        if (!$block) {
            $block = \Mooc\DB\Block::create(array('type' => 'HtmlBlock', 'seminar_id' => '', 'title' => 'LandingPage'));
        }

        $this->ui_block = $this->plugin->getBlockFactory()->makeBlock($block);
        $this->data     = $this->ui_block->getFields();
        $this->context  = clone Request::getInstance();
        $this->view     = 'student';
        $this->root     = $this->plugin->getCurrentUser()->getPerm() == 'root';

        if ($edit && $this->root) {
            $this->view = 'author';
        }
    }

    function store_overview_action()
    {
        if (Navigation::hasItem('/mooc/overview')) {
            Navigation::activateItem('/mooc/overview');
        }

        if ($this->plugin->getCurrentUser()->getPerm() != 'root') {
            throw new AccessDeniedException('You need to be root to edit the overview-page');
        }

        $block = current(\Mooc\DB\Block::findBySQL('seminar_id IS NULL AND parent_id IS NULL'));

        if (!$block) {
            $block = \Mooc\DB\Block::create(array('type' => 'HtmlBlock'));
        }

        $ui_block = $this->plugin->getBlockFactory()->makeBlock($block);
        $ui_block->handle('save', array('content' => Request::get('content')), false);

        $this->redirect('courses/overview');
    }

    public function show_action($cid)
    {
        if (strlen($cid) !== 32) {
            throw new Trails_Exception(400);
        }

        if ($GLOBALS['SessionSeminar'] && Navigation::hasItem('/course/mooc_overview/overview')) {
            Navigation::activateItem("/course/mooc_overview/overview");
        } else {
            $this->plugin->fixCourseNavigation();
        }

        $user_id = $this->plugin->getCurrentUser()->id;
        $admission = new AdmissionApplication(array($user_id, $cid));

        if (!$admission->isNew()) {
            $this->preliminary = true;
        }

        $this->courseware = \Mooc\DB\Block::findCourseware($cid);
        $this->course = Course::find($cid);
        /** @var \DataFieldEntry[] $localEntries */
        $localEntries = DataFieldEntry::getDataFieldEntries($cid);
        foreach ($localEntries as $entry) {
            /** @var \DataFieldStructure $structure */
            $structure = $entry->structure;
            if ($structure->accessAllowed($GLOBALS['perm'])) {
                if ($entry->getValue()) {
                    foreach ($this->plugin->getDataFields() as $field => $id) {
                        if ($entry->getId() == $id) {
                            $this->$field = $entry->getValue();
                        }
                    }
                }
            }
        }

        if ($this->preview_image === null && preg_match(VideoBlock::YOUTUBE_PATTERN, $this->preview_video)) {
            $this->preview_video = VideoBlock::cleanUpYouTubeUrl($this->preview_video);
            $this->preview_image = '//img.youtube.com/vi/'.basename($this->preview_video).'/0.jpg';
        }
    }

    public function leave_action($cid)
    {
        CSRFProtection::verifyUnsafeRequest();

        $course  = Course::find($cid);
        $user_id = $this->plugin->getCurrentUser()->id;

        if (LockRules::Check($cid, 'participants')) {
            $lockdata = LockRules::getObjectRule($cid);

            $data = array('course' => $course->name);
            if ($lockdata['description']) {
                $data['description'] = formatLinks($lockdata['description']);
            }

            return $this->json_error(_('Das Abonnement der Veranstaltung kann nicht aufgehoben werden.'), 403, $data);
        }

        $old_school = new Seminar($course);
        $status = $old_school->deleteMember($user_id);

        if (!$status) {
            return $this->json_error(_('Das Abonnement der Veranstaltung konnte nicht aufgehoben werden.'), 400);
        }
        $this->render_json(array('status' => 'success'));
    }
}
