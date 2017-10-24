<?php
// CPO -> course progress overview
class CpoController extends CoursewareStudipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm('tutor', $this->plugin->getCourseId())) {
            throw new Trails_Exception(401);
        }

        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css');
       
        $courseware = $this->container['current_courseware'];
        $title = Request::option('cid', false)
               ? $_SESSION['SessSemName']['header_line'] . ' - '
               : '';
        $title .= $courseware->title." - Fortschrittsübersicht für Dozenten";
        PageLayout::setTitle($title);

        if (Navigation::hasItem('/course/mooc_cpo')) {
            Navigation::activateItem("/course/mooc_cpo");
        }
        $teachers = (new \CourseMember())->findByCourseAndStatus(array($this->plugin->getCourseId()), "dozent");
        $dids = array_map(function($teacher){return $teacher->user_id;} , $teachers); // dozent ids
        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId()));
        $bids   = array_map(function ($block) { return (int) $block->id; }, $blocks); // block ids
        $progress = array_reduce(
            \Mooc\DB\UserProgress::findBySQL('block_id IN (?) AND user_id NOT IN (?)', array($bids, $dids)),
            function ($memo, $item) {
                if (array_key_exists($item->block_id,$memo)) {
                    $stored_grade = $memo[$item->block_id]['grade'];
                    $users = $memo[$item->block_id]['users']+1;
                    $grade = ($stored_grade+$item->grade) / $users;
                    $memo[$item->block_id]['date'] > $item->chdate ? $date = $memo[$item->block_id]['date'] : $date = $item->chdate;
                    $memo[$item->block_id] = array(
                        'grade' => $grade,
                        'max_grade' => $item->max_grade,
                        'users' => $users,
                        'date' => $date
                    );
                    
                } else {
                    $memo[$item->block_id] = array(
                        'grade' => $item->grade,
                        'max_grade' => $item->max_grade,
                        'users' => 1,
                        'date' => $item->chdate
                    );
                }
                return $memo;
            },
            array());

        $members = count((new \CourseMember())->findByCourseAndStatus(array($this->plugin->getCourseId()), "autor"));
        foreach ($progress as &$block) {
            if($block["users"] < $members) {
                $block["grade"] =($block["grade"]*$block["users"])/$members;
            }
        }

        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId())),
            function($memo, $item) {
                $memo[$item->parent_id][] = $item->toArray();
                return $memo;
            },
            array());
        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $progress, $this->courseware);
        $this->usage = $this->getUsage();
    }

    private function getUsage()
    {
        $teachers = (new \CourseMember())->findByCourseAndStatus(array($this->plugin->getCourseId()), "dozent");
        $dids = array_map(function($teacher){return $teacher->user_id;} , $teachers); // dozent ids
        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position', array($this->plugin->getCourseId()));
        $bids   = array_map(function ($block) { return (int) $block->id; }, $blocks); // block ids
        $progress = \Mooc\DB\UserProgress::findBySQL('block_id IN (?) AND user_id NOT IN (?)', array($bids, $dids));
        $usage = array();
        foreach ($progress as $block){
            if(strtotime($block->chdate) > 0){
                $usage[date('N', strtotime($block->chdate))] += 1;
                $usage[0] += 1;
            }
        }
        return $usage;
    }

    private function buildTree($grouped, $progress, &$root)
    {
        $this->addChildren($grouped, $root);
        if ($root['type'] !== 'Section') {
            foreach($root['children'] as &$child) {
                $this->buildTree($grouped, $progress, $child);
            }
            $root['progress'] = $this->computeProgress($root);
            $root['date'] = $this->setDate($progress, $root);
        }

        else {
            $root['children'] = $this->addChildren($grouped, $root);
            if ($root['children']) {
                $grades = array_map(
                    function ($block) use ($progress) {
                        return (double) $progress[$block['id']]['grade'];
                    },
                    $root['children']
                );
                $maxGrades = array_map(
                    function ($block) use ($progress) {
                        return (double) $progress[$block['id']]['max_grade'];
                    },
                    $root['children']
                );

                if (array_sum($maxGrades) > 0) {
                    $root['progress'] = array_sum($grades) / array_sum($maxGrades);
                } else {
                    $root['progress'] = 0;
                }
            }
            else {
                $root['progress'] = 0;
            }
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = array_filter(
            isset($grouped[$parent['id']]) ? $grouped[$parent['id']] : array(),
            function ($item) {
                return $item['publication_date'] <= time();
            });
        return $parent['children'];
    }

    private function computeProgress(&$block)
    {
        if (!sizeof($block['children'])) {
            return 0;
        }
        return
            array_sum(
                array_map(
                    function ($section) {return $section['progress']; },
                    $block['children'])
            ) / sizeof($block['children']);
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
