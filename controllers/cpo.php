<?php

use Mooc\DB\Post as Post;

// CPO -> course progress overview
class CpoController extends CoursewareStudipController
{
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

        if (Navigation::hasItem('/course/mooc_courseware/progressoverview')) {
            Navigation::activateItem('/course/mooc_courseware/progressoverview');
        }

        $teachers = (new \CourseMember())->findByCourseAndStatus(array($this->plugin->getCourseId()), 'dozent');
        $members = (new \CourseMember())->findByCourseAndStatus(array($this->plugin->getCourseId()), 'autor');
        $dids = array_map(function($teacher){return $teacher->user_id;} , $teachers); // dozent ids
        $mids = array_map(function($members){return $members->user_id;} , $members); // member ids

        $this->uid = Request::get('uid');
        if ($this->uid && $GLOBALS['perm']->have_studip_perm('user', $this->plugin->getCourseId(), $this->uid)) {
            $mids = [$this->uid];
        } else {
            $this->uid = null;
        }

        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId()));
        $bids   = array_map(function ($block) { return (int) $block->id; }, $blocks); // block ids
        $progress = array_reduce(
            \Mooc\DB\UserProgress::findBySQL('block_id IN (?) AND user_id NOT IN (?) AND user_id IN (?)', array($bids, $dids, $mids)),
            function ($memo, $item) {
                if (array_key_exists($item->block_id,$memo)) {
                    $stored_grade = $memo[$item->block_id]['grade'];
                    $users = $memo[$item->block_id]['users']+1;
                    $grade = ($stored_grade+ $item->grade);
                    $memo[$item->block_id]['date'] > $item->chdate ? $date = $memo[$item->block_id]['date'] : $date = $item->chdate;
                    $memo[$item->block_id] = array(
                        'grade' => $grade,
                        'max_grade' => $item->max_grade,
                        'users' => $users,
                        'date' => $date
                    );
                } else {
                    $memo[$item->block_id] = array(
                        'grade' => (int)$item->grade,
                        'max_grade' => $item->max_grade,
                        'users' => 1,
                        'date' => $item->chdate
                    );
                }

                return $memo;
            },
            array());

        $members_count = count((new \CourseMember())->findByCourseAndStatus(array($this->plugin->getCourseId()), 'autor'));

        if ($this->uid) {
            $members_count = 1;
        }

        foreach ($progress as &$block) {
            $block['grade'] = $block['grade'] / $block['users'];
            if($block['users'] < $members) {
                $block['grade'] = ($block['grade'] * $block['users']) / $members_count;
            }
        }

        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY position, id', array($this->plugin->getCourseId())),
            function($memo, $item) {
                $memo[$item->parent_id][] = $item->toArray();
                return $memo;
            },
            array());
        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $progress, $this->courseware);
        $this->usage = $this->getUsage();
    }

    public function postoverview_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm('tutor', $this->plugin->getCourseId())) {
            throw new Trails_Exception(401);
        }

        if (Navigation::hasItem('/course/mooc_courseware/postoverview')) {
            Navigation::activateItem('/course/mooc_courseware/postoverview');
        }

        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css');
        PageLayout::addScript($this->plugin->getPluginURL().'/assets/js/postoverview.js');

        $this->cid = $this->plugin->getCourseId();
        $this->threads = array();
        $thread_ids = \Mooc\DB\Post::getAllThreadIds($this->cid);

        $this->thrads_in_blocks = $this->getThreadsInBlocks();

        foreach ($thread_ids as $thread_id){
            $thread = array(
                'thread_id' => $thread_id, 
                'thread_title' => \Mooc\DB\Post::findPost($thread_id, 0, $this->cid)['content'],
                'thread_posts' => \Mooc\DB\Post::findPosts($thread_id, $this->cid, $this->container['current_user']['id'])
            );
            array_push($this->threads, $thread);
        }
    }

    public function download_thread_action()
    {
        if ((Request::get('thread_id') != '') && (Request::get('cid') != '')) { 
            $thread_id = Request::get('thread_id');
            $cid = Request::get('cid');
        } else {
            return $this->redirect('cpo/postoverview?download=false');
        }

        $thread = array(
            'thread_id' => $thread_id, 
            'thread_title' => \Mooc\DB\Post::findPost($thread_id, 0, $cid)['content'],
            'thread_posts' => \Mooc\DB\Post::findPosts($thread_id, $cid, $GLOBALS['user']->id)['posts']
        );

        $f = fopen('php://output', 'w');

        $csv_header = array('user_name','date','content');
        fputcsv($f, $csv_header, ',');

        foreach($thread['thread_posts'] as $post) {
            $line = array($post['user_name'], $post['date'] , $post['content']);
           fputcsv($f, $line, ',');

        }

        $filename = str_replace(' ', '_', $thread['thread_title']);

        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'.csv";');
        fpassthru($f);
        exit();
    }

    public function answer_action()
    {
        if ((Request::get('thread_id') != '') && (Request::get('content') != '') && (Request::get('cid') != '')) { 
            $thread_id = Request::get('thread_id');
            $content = Request::get('content');
            $answer = 'answer=true';
            $cid = Request::get('cid');
        } else {
            return $this->redirect('cpo/postoverview?answer=false');
        }

        $post_id = Post::getNextPostId($thread_id, $cid);

        $data = array(
                'thread_id' => $thread_id ,
                'post_id' => $post_id,
                'seminar_id' => $cid,
                'user_id' => $GLOBALS['user']->id,
                'content' => $content,
                'mkdate' => (new \DateTime())->format('Y-m-d H:i:s'),
                'chdate' => (new \DateTime())->format('Y-m-d H:i:s')
            );

        if (Post::create($data)) {
            $answer = 'answer=true&thread_id='.$thread_id;
        } else {
            $answer = 'answer=false';
        }

        return $this->redirect('cpo/postoverview?'.$answer);
    }

    public function hide_post_action()
    {
        if ((Request::get('thread_id') != '') && (Request::get('post_id') != '') && (Request::get('cid') != '')) { 
            $thread_id = Request::get('thread_id');
            $post_id = Request::get('post_id');
            $cid = Request::get('cid');
            $hide = Request::get('hide_post');
        } else {
            return $this->redirect('cpo/postoverview?hide=false');
        }
        $hidden = Post::hidePost($thread_id, $post_id, $cid, $hide);

        return $this->redirect('cpo/postoverview?hide='.$hidden.'&thread_id='.$thread_id);
    }

    public function edit_title_action()
    {
        if ((Request::get('thread_id') != '') && (Request::get('thread_title') != '')) { 
            $thread_id = Request::get('thread_id');
            $thread_title = \STUDIP\Markup::purifyHtml(Request::get('thread_title'));
            $cid = $this->plugin->getCourseId();
        } else {
            return $this->redirect('cpo/postoverview?update=false');
        }
        Post::alterPost($thread_id, 0, $cid, $thread_title);

        return $this->redirect('cpo/postoverview?update=true&thread_id='.$thread_id);
    }

    public function remove_thread_action()
    {
        if (Request::get('thread_id') != '') { 
            $thread_id = Request::get('thread_id');
            $cid = $this->plugin->getCourseId();
        } else {
            return $this->redirect('cpo/postoverview?remove=false');
        }
        Post::removeThread($thread_id, $cid);

        return $this->redirect('cpo/postoverview?remove=true&thread_id='.$thread_id);
    }

    private function getThreadsInBlocks()
    {
        $db = \DBManager::get();
        $stmt = $db->prepare("
            SELECT 
                mooc_blocks.id , mooc_fields.json_data 
            FROM 
                mooc_blocks 
            INNER JOIN 
                mooc_fields  
            ON 
                mooc_blocks.seminar_id = :cid
            AND 
                mooc_blocks.type = 'PostBlock' 
            AND 
                mooc_blocks.id = mooc_fields.block_id 
            AND 
                mooc_fields.name = 'thread_id'
        ");
        $stmt->bindParam(':cid', $this->plugin->getCourseId());
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $list = array();
        foreach ($result as $item){
            $block =  (new \Mooc\DB\Block($item['id']));
            $link = \PluginEngine::getURL('courseware/courseware').'&selected='.$block->parent_id;
            $title = $block->parent->parent->parent['title'].'>'.$block->parent->parent['title'].'>'.$block->parent['title'];
            if (array_key_exists(str_replace('"', '', $item['json_data']), $list) ){
                array_push($list[str_replace('"', '', $item['json_data'])], array('link'=> $link, 'title' => $title));
            } else {
                $list[str_replace('"', '', $item['json_data'])] = array(array('link'=> $link, 'title' => $title));
            }
        }

        return $list;
    }

    private function getUsage()
    {
        $teachers = (new \CourseMember())->findByCourseAndStatus(array($this->plugin->getCourseId()), 'dozent');
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
            if($root['children']) {
                foreach($root['children'] as &$child) {
                    $this->buildTree($grouped, $progress, $child);
                }
            }
            $root['progress'] = $this->computeProgress($root);
            $root['date'] = $this->setDate($progress, $root);
        } else {
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
                        if ($progress[$block['id']]['max_grade'] == null) {
                            return 1;
                        }
                        return (double) $progress[$block['id']]['max_grade'];
                    },
                    $root['children']
                );

                if (array_sum($maxGrades) > 0) {
                    $root['progress'] = array_sum($grades) / array_sum($maxGrades);
                } else {
                    $root['progress'] = 1;
                }
            }
            else {
                $root['progress'] = 1;
            }
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = $grouped[$parent['id']];
        if ($parent['children']) {
            usort($parent['children'], function($a, $b) {
                    return $a['position'] - $b['position'];
            });
        }

        return $parent['children'];
    }

    private function computeProgress(&$block)
    {
        if (!($block['children'])) {
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
        if (!($block['children'])) {
            return null;
        }

        $date = date('');
	foreach ($block['children'] as $section) {
	    if ($section['children']) {
                foreach($section['children']as $blocks) {
                   if ($b = $progress[$blocks['id']]) {
                        if ($date < date($b['date'])){ 
                            $date = date($b['date']);
                        }
                    }
                }
	    }
        }

        return $date;
    }
}
