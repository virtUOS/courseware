<?php

use Mooc\DB\Block;

require_once dirname(__FILE__) . '/../pdf/coursewareExportPDF.php';

/**
 * PDF Exporter.
 *
 * @author <lunzenauer@elan-ev.de>
 */
class PdfExportController extends CoursewareStudipController
{
    public function index_action()
    {
        if (!$this->container['current_user']->canCreate($this->container['current_courseware'])) {
            throw new Trails_Exception(401);
        }
        $this->cid = Request::get('cid');

        \Assets::set_assets_url(parse_url($GLOBALS['ASSETS_URL'])['path']);
        $grouped = $this->getGrouped($this->cid);

        $courseware = current($grouped['']);
        $this->buildTree($grouped, $courseware);

        $pdf = $this->visitTree($courseware);
        $pdf->Output('courseware-export.pdf', 'I');
        exit();
    }

    private function visitTree($courseware)
    {
        $pdf = new CoursewareExportPDF();
        $this->visitItem($pdf, $courseware);

        return $pdf;
    }

    private function visitItem($pdf, $item, $index = [])
    {
        $this->writeItem($pdf, $item, $index);
        if ($item['children']) {
            foreach ($item['children'] as $childIndex => $child) {
                $this->visitItem($pdf, $child, array_merge($index, [$childIndex + 1]));
            }
        }
    }

    private function writeItem($pdf, $item, $index)
    {
        switch ($item['type']) {
            case 'Courseware':
                $this->writeCourseware($pdf, $item, $index);
                break;
            case 'Chapter':
                $this->writeChapter($pdf, $item, $index);
                break;
            case 'Subchapter':
                $this->writeSubchapter($pdf, $item, $index);
                break;
            case 'Section':
                $this->writeSection($pdf, $item, $index);
                break;
            default:
                if ($item['isBlock']) {
                    $this->writeBlock($pdf, $item, $index);
                }
                break;
        }
    }

    private function writeCourseware($pdf, $courseware)
    {
        $pdf->AddPage();
        $html = '<h1>Courseware</h1>';
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function writeChapter($pdf, $chapter, $index)
    {
        $pdf->AddPage();
        $html = sprintf('<h2>%s) %s</h2>', join('.', $index), $chapter['title']);
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function writeSubchapter($pdf, $subchapter, $index)
    {
        $html = sprintf('<h3>%s) %s</h3>', join('.', $index), $subchapter['title']);
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function writeSection($pdf, $section, $index)
    {
        $html = sprintf('<h4>%s) %s</h4>', join('.', $index), $section['title']);
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function writeBlock($pdf, $block, $index)
    {
        $html = sprintf('<h5>%s) Block</h5>', join('.', $index), $block['title']);
        $html .= $block['data'];
        $pdf->writeHTML($html, true, false, true, false, '');
    }

    private function getGrouped($cid)
    {
        $grouped = array_reduce(
            Block::findBySQL('seminar_id = ? ORDER BY id, position', [$cid]),
            function ($memo, $item) {
                $arr = $item->toArray();
                $arr['isStrucutalElement'] = true;
                $arr['childType'] = $this->getSubElement($arr['type']);
                if (!$item->isStructuralBlock()) {
                    $arr['isStrucutalElement'] = false;
                    $arr['isBlock'] = true;
                    $ui_block = $this->plugin->getBlockFactory()->makeBlock($item);
                    $arr['data'] = $ui_block->render('pdfexport');
                    if ('TestBlock' == $item->type) {
                        $arr['student_view'] = json_encode($ui_block->student_view());
                    }
                }
                if ('Section' == $item->type) {
                    $ui_block = $this->plugin->getBlockFactory()->makeBlock($item);
                    $arr['icon'] = $ui_block->icon;
                }
                $memo[$item->parent_id][] = $arr;

                return $memo;
            },
            []
        );

        return $grouped;
    }

    private function getSubElement($type)
    {
        $sub_element = null;
        switch ($type) {
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
        if ('Section' !== $root['type']) {
            if (!empty($root['children'])) {
                foreach ($root['children'] as &$child) {
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
        if (null != $parent['children']) {
            usort($parent['children'], function ($a, $b) {
                return $a['position'] - $b['position'];
            });
        }

        return $parent['children'];
    }
}
