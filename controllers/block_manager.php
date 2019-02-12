<?php

use Mooc\DB\Block;
use Mooc\Export\Validator\XmlValidator;
use Mooc\Import\XmlImport;

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

        if (Navigation::hasItem('/course/mooc_courseware/block_manager')) {
            Navigation::activateItem('/course/mooc_courseware/block_manager');
        }
        
        $blocks = \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId()));
        
        $grouped = array_reduce(
            \Mooc\DB\Block::findBySQL('seminar_id = ? ORDER BY id, position', array($this->plugin->getCourseId())),
            function($memo, $item) {
                $memo[$item->parent_id][] = $item->toArray();
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

}
