<?php

use Mooc\DB\Block as dbBlock;
use Mooc\Import\XmlImport;
use Mooc\Export\XmlExport;
use Mooc\Export\Validator\XmlValidator;

/**
 * Export for Courseware Player
 *
 * @author Ron Lucke <lucke@elan-ev.de>
 */
class CwpExportController extends CoursewareStudipController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (Navigation::hasItem('/course/mooc_courseware/block_manager')) {
            Navigation::activateItem('/course/mooc_courseware/block_manager');
        }
    }

    public function index_action()
    {
        if (!$this->container['current_user']->canCreate($this->container['current_courseware'])) {
            throw new Trails_Exception(401);
        }
        $this->cid = Request::get('cid');
        $grouped = $this->getGrouped($this->cid);

        $this->courseware = current($grouped['']);
        $this->buildTree($grouped, $this->courseware);

        $this->courseware['courseware_name'] = (string) Course::find($this->cid)->name;

        //create a temporary directory
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);
        mkdir($tempDir.'/cwData');

        file_put_contents($tempDir.'/cwData/courseware.js', 'var COURSEWARE = ' . json_encode($this->courseware));

        $courseware = $this->container['current_courseware'];
        foreach ($courseware->getFiles() as $file) {
            if (trim($file['url']) !== '') {
                continue;
            }

            $destination = $tempDir . '/cwData/' . $file['id'];
            if (!is_dir($destination) && file_exists($file['path'])) {
                mkdir($destination);
                copy($file['path'], $destination.'/'.$file['filename']);
            }
        }
        $zip_cwp = new ZipArchive;
        if($zip_cwp->open(dirname(__FILE__).'/../assets/cwp/cwp.zip') == true) {
            $zip_cwp->extractTo($tempDir);
            $zip_cwp->close();
        }

        $zipFile = $GLOBALS['TMP_PATH'].'/'.uniqid().'.zip';
        FileArchiveManager::createArchiveFromPhysicalFolder($tempDir, $zipFile);
        $this->set_layout(null);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=courseware.zip');

        while (ob_get_level()) {
            ob_end_flush();
        }
        readfile($zipFile);

        $this->deleteRecursively($tempDir);
        $this->deleteRecursively($zipFile);

        exit;

    }

    private function getGrouped($cid, $remote = false)
    {
        $grouped = array_reduce(
            dbBlock::findBySQL('seminar_id = ? ORDER BY id, position', array($cid)),
            function($memo, $item) use($remote) {
                $arr = $item->toArray();
                $arr['isRemote'] = false;
                if ($remote) {
                    $arr['isRemote'] = true;
                }
                $arr['isStrucutalElement'] = true;
                $arr['childType'] = $this->getSubElement($arr['type']);
                if (!$item->isStructuralBlock()) {
                    $arr['isStrucutalElement'] = false;
                    $arr['isBlock'] = true;
                    $ui_block = $this->plugin->getBlockFactory()->makeBlock($item);
                    //$arr['ui_block'] = $ui_block;
                    $arr['data'] = $ui_block->getHtmlExportData();
                    // $arr['metadata'] = json_encode($ui_block->getFields()) ;
                    if($item->type == 'TestBlock') {
                        $arr['student_view'] = json_encode($ui_block->student_view()) ;
                    }
                }
                if($item->type == 'Section') {
                    $ui_block = $this->plugin->getBlockFactory()->makeBlock($item);
                    $arr['icon'] = $ui_block->icon;
                }
                $memo[$item->parent_id][] = $arr;
                return $memo;
            },
            array());

        return $grouped;
    }

    private function getSubElement($type) {
        $sub_element = null;
        switch($type) {
            case 'Courseware':
                $sub_element = 'Chapter';
                break;
            case 'Chapter':
                $sub_element = 'Subchapter';
                break;
            case 'Subchapter':
                $sub_element = 'Section';
                break;
            case 'Section':
                $sub_element = 'Block';
                break;
            case 'Block':
            default:
        }

        return $sub_element;
    }

    private function buildTree($grouped, &$root)
    {
        $this->addChildren($grouped, $root);
        if ($root['type'] !== 'Section') {
            if (!empty($root['children'])) {
                foreach($root['children'] as &$child) {
                    $this->buildTree($grouped, $child);
                }
            }
        } else {
            $root['children'] = $this->addChildren($grouped, $root);
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent['children'] = $grouped[$parent['id']];
        if ($parent['children'] != null) {
            usort($parent['children'], function($a, $b) {
                return $a['position'] - $b['position'];
            });
        }

        return $parent['children'];
    }

    private function deleteRecursively($path)
    {
        if (is_dir($path)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                /** @var SplFileInfo $file */
                if (in_array($file->getBasename(), array('.', '..'))) {
                    continue;
                }

                if ($file->isFile() || $file->isLink()) {
                    unlink($file->getRealPath());
                } else if ($file->isDir()) {
                    rmdir($file->getRealPath());
                }
            }

            rmdir($path);
        } else if (is_file($path) || is_link($path)) {
            unlink($path);
        }
    }

}
