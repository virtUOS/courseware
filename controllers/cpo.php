<?php

class CpoController extends CoursewareStudipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css');
        

        if (Navigation::hasItem('/course/mooc_cpo')) {
            Navigation::activateItem("/course/mooc_cpo");
        }

        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position', array($this->plugin->getCourseId()));
        $bids   = array_map(function ($block) { return (int) $block->id; }, $blocks);
        $progress = array_reduce(
            \Mooc\DB\UserProgress::findBySQL('block_id IN (?) ', array($bids)),
            function ($memo, $item) {
                if (array_key_exists($item->block_id,$memo)) {
                    $stored_grade = $memo[$item->block_id]['grade'];
                    $users = $memo[$item->block_id]['users']+1;
                    $grade = ($stored_grade+$item->grade) / $users;
                    $memo[$item->block_id] = array(
                        'grade' => $grade,
                        'max_grade' => $item->max_grade,
                        'users' => $users
                    );
                    
                } else {
                    $memo[$item->block_id] = array(
                        'grade' => $item->grade,
                        'max_grade' => $item->max_grade,
                        'users' => 1
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
    }

    private function buildTree($grouped, $progress, &$root)
    {
        $this->addChildren($grouped, $root);
        if ($root['type'] !== 'Section') {
            foreach($root['children'] as &$child) {
                $this->buildTree($grouped, $progress, $child);
            }
            $root['progress'] = $this->computeProgress($root);
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
}
