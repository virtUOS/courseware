<?php

use Mooc\DB\Block;

/**
 * Controller to manage Courseware Blocks
 *
 * @author Ron Lucke <lucke@elan-ev.de>
 */
class BlockManagerController extends CoursewareStudipController
{
    public function index_action()
    {
        if (!$GLOBALS['perm']->have_studip_perm('tutor', $this->plugin->getCourseId())) {
            throw new Trails_Exception(401);
        }

        PageLayout::addStylesheet($this->plugin->getPluginURL().'/assets/static/courseware.css');
        PageLayout::addScript($this->plugin->getPluginURL().'/assets/js/block_manager.js');

        if (Navigation::hasItem('/course/mooc_courseware/block_manager')) {
            Navigation::activateItem('/course/mooc_courseware/block_manager');
        }

        $this->cid = Request::get('cid');
        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId())),
            function($memo, $item) {
                $arr = $item->toArray();
                if (!$item->isStructuralBlock()) {
                    $arr['isBlock'] = true;
                    $arr['ui_block'] = $this->plugin->getBlockFactory()->makeBlock($item);
                }
                $memo[$item->parent_id][] = $arr;
                return $memo;
            },
            array());
            
        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $this->courseware);
    }

    private function buildTree($grouped, &$root)
    {
        $this->addChildren($grouped, $root);
        if ($root['type'] !== 'Section') {
            foreach($root['children'] as &$child) {
                $this->buildTree($grouped, $child);
            }
        } else {
            $root['children'] = $this->addChildren($grouped, $root);
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = $grouped[$parent['id']];
        usort($parent['children'], function($a, $b) {
            return $a['position'] - $b['position'];
        });

        return $parent['children'];
    }
    
    public function store_changes_action()
    {
        $cid = Request::get('cid');
        $chapterList = json_decode(Request::get('chapterList'), true);
        $subchapterList = json_decode(Request::get('subchapterList'), true);
        $sectionList = json_decode(Request::get('sectionList'), true);
        $blockList = json_decode(Request::get('blockList'), true);

        foreach(array($subchapterList, $sectionList, $blockList) as $list) {
            foreach($list as $key => $value) {
                $parent = \Mooc\DB\Block::find($key);
                foreach($value as $bid) {
                    $block = \Mooc\DB\Block::find($bid);
                    if ($parent->id != $block->parent_id) {
                        $block->parent_id = $parent->id;
                        $block->store();
                    }
                }
                $parent->updateChildPositions($value);
            }
        }

        $courseware = \Mooc\DB\Block::findCourseware($cid);
        $courseware->updateChildPositions($chapterList);

        return $this->redirect('block_manager?cid='.$cid.'&stored=true');
    }

}
