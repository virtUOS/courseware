<?php

use Mooc\DB\MailLog;

class ProgressController extends CoursewareStudipController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if ($this->container['current_user']->isNobody()) {
            return false;
        }

        $this->user = $this->container['current_user'];
    }

    public function index_action()
    {
        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css');

        if (Navigation::hasItem('/course/mooc_courseware/progress')) {
            Navigation::activateItem('/course/mooc_courseware/progress');
        }

        $cid  =$this->plugin->getCourseId();
        $uid = $this->plugin->getCurrentUserId();

        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($cid));
        $bids = array_map(function ($block) { return (int) $block->id; }, $blocks);
        $progress = array_reduce(
            \Mooc\DB\UserProgress::findBySQL('block_id IN (?) AND user_id = ?', array($bids, $uid)),
            function ($memo, $item) {
                $memo[$item->block_id] = array(
                    'grade' => $item->grade,
                    'max_grade' => $item->max_grade,
                    'date' => $item->chdate
                );

                return $memo;
            },
            array());

        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position, id', array($cid)),
            function ($memo, $item) {
                $arr = $memo[$item->parent_id][] = array_merge($item->toArray(), ['db_block'=> $item]);

                return $memo;
            },
            array());

        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $progress, $this->courseware);

        $courseware = \Mooc\DB\Block::findCourseware($cid);
        $courseware_ui = $this->plugin->getBlockFactory()->makeBlock($courseware);

        $this->hasCert = MailLog::hasCertificate($uid,$cid) && $courseware_ui->getCertificate();
    }

    public function reset_action()
    {
        $uid = $this->plugin->getCurrentUserId();
        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId()));
        $bids = array_map(function ($block) { return (int) $block->id; }, $blocks);

        Mooc\DB\UserProgress::deleteBySQL('block_id IN (?) AND user_id = ?', array($bids, $uid));

        return $this->redirect('progress?cid='.$this->plugin->getCourseId());
    }

    public function certificate_action()
    {
        $cid  =$this->plugin->getCourseId();

        if(!MailLog::hasCertificate($this->plugin->getCurrentUserId(),$cid)) {
            return $this->redirect('progress?cid='.$cid);
        }

        require_once dirname(__FILE__).'/../pdf/coursewareCertificatePDF.php';

        $user = User::find($this->plugin->getCurrentUserId());
        $course = Course::find($cid);

        $template_factory = new Flexi_TemplateFactory(dirname(__FILE__) . '/../views');
        $template = $template_factory->open('mails/_pdf_certificate');
        $html = $template->render(compact('user', 'course'));

        $courseware = \Mooc\DB\Block::findCourseware($cid);
        $courseware_ui = $this->plugin->getBlockFactory()->makeBlock($courseware);

        $file_ref = new \FileRef($courseware_ui->getCertificateImageId());
        if ($file_ref) {
            $file =  new \File($file_ref->file_id);
            $background_image = $file['path'];
        } else {
            $background_image = false;
        }

        $pdf = new CoursewareCertificatePDF($background = $background_image);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        $filename = _('Zertifikat');
        $pdf->Output($user->nachname.'_'.$course->name.'_'.$filename.'.pdf', 'I');
        die;
    }

    private function buildTree($grouped, $progress, &$root)
    {
        $this->addChildren($grouped, $root);

        if ($root['type'] !== 'Section') {
            foreach ($root['children'] as &$child) {
                $this->buildTree($grouped, $progress, $child);
            }
            $root['progress'] = $this->computeProgress($root);
            $root['date'] = $this->setDate($progress, $root);
        } else {
            $root['children'] = $this->addChildren($grouped, $root);
            if ($root['children']) {
                $grades = array_map(
                    function ($block) use ($progress) {
                        return (float) $progress[$block['id']]['grade'];
                    },
                    $root['children']
                );
                $maxGrades = array_map(
                    function ($block) use ($progress) {
                        if ($progress[$block['id']]['max_grade'] == null) {
                            return 1;
                        }
                        return (float) $progress[$block['id']]['max_grade'];
                    },
                    $root['children']
                );

                if (array_sum($maxGrades) > 0) {
                    $root['progress'] = array_sum($grades) / array_sum($maxGrades);
                } else {
                    $root['progress'] = 1;
                }
            } else {
                $root['progress'] = 1;
            }
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = array_filter(
            isset($grouped[$parent['id']]) ? $grouped[$parent['id']] : array(),
            function ($item) {
                $db_block = $item['db_block'];

                return $db_block->isPublished() && $db_block->isVisible() && $this->user->canRead($db_block);
            });

        return $parent['children'];
    }

    private function computeProgress(&$block)
    {
        if (!sizeof($block['children'])) {
            return 1;
        }

        return array_sum(
                array_map(
                    function ($section) {return $section['progress']; },
                    $block['children'])
            ) / sizeof($block['children']
        );
    }

    private function setDate($progress, &$block)
    {
        if (!sizeof($block['children'])) {
            return null;
        }
        $date = date('');
        foreach ($block['children'] as $section) {
            foreach($section['children']as $blocks) {
               if ($b = $progress[$blocks['id']]) {
                    if ($date < date($b['date'])){ 
                        $date = date($b['date']);
                    }
                }
            }
        }

        return $date;
    }
}
