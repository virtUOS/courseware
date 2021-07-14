<?php

use Mooc\DB\Block;

require_once dirname(__FILE__) . "/../pdf/coursewareExportPDF.php";

/**
 * PDF Exporter.
 *
 * @author <lunzenauer@elan-ev.de>
 */
class PdfExportController extends CoursewareStudipController
{
    public function index_action()
    {
        if (
            !$this->container["current_user"]->canCreate(
                $this->container["current_courseware"]
            )
        ) {
            throw new Trails_Exception(401);
        }
        $this->cid = Request::get("cid");
        \URLHelper::setBaseURL($GLOBALS["ABSOLUTE_URI_STUDIP"]);

        $grouped = $this->getGrouped($this->cid);

        $courseware = current($grouped[""]);
        $this->buildTree($grouped, $courseware);

        $pdf = $this->visitTree($courseware);
        $pdf->Output("courseware-export.pdf", "I");
        exit();
    }

    private function visitTree($courseware)
    {
        $pdf = new CoursewareExportPDF();

        // create links to all items
        $this->visitItem($pdf, $courseware, [], [$this, "createLinkToItem"]);

        // write items to pdf
        $this->visitItem($pdf, $courseware, [], [$this, "writeItem"]);

        // write TOC
        $pdf->addTOCPage();
        $pdf->MultiCell(0, 0, _cw("Inhaltsverzeichnis"), 0, "C");
        $pdf->Ln();
        $pdf->addTOC(2, "dejavusans", " ", _cw("Inhaltsverzeichnis"));
        $pdf->endTOCPage();

        return $pdf;
    }

    private function visitItem($pdf, $item, $index, $callback)
    {
        $callback($pdf, $item, $index);
        if ($item["children"]) {
            foreach ($item["children"] as $childIndex => $child) {
                $this->visitItem(
                    $pdf,
                    $child,
                    array_merge($index, [$childIndex + 1]),
                    $callback
                );
            }
        }
    }

    private function createLinkToItem($pdf, $item, $index)
    {
        $pdf->addLinkToItem($item["id"]);
    }

    private function writeItem($pdf, $item, $index)
    {
        if ($link = $pdf->getLinkToItem($item["id"])) {
            $pdf->SetLink($link);
        }

        switch ($item["type"]) {
            case "Courseware":
                $this->writeCourseware($pdf, $item, $index);
                break;
            case "Chapter":
                $this->writeChapter($pdf, $item, $index);
                break;
            case "Subchapter":
                $this->writeSubchapter($pdf, $item, $index);
                break;
            case "Section":
                $this->writeSection($pdf, $item, $index);
                break;
            default:
                if ($item["isBlock"]) {
                    $this->writeBlock($pdf, $item, $index);
                }
                break;
        }
    }

    private function writeCourseware($pdf, $courseware)
    {
        $pdf->AddPage();
        $html =
            '<h1>Courseware</h1><p style="font-style:italic;">' .
            date("c", time()) .
            "</p>";
        $pdf->writeHTML($html);
    }

    private function writeChapter($pdf, $chapter, $index)
    {
        $heading = sprintf("%s. %s", join(".", $index), $chapter["title"]);
        $html = "<h2>$heading</h2";

        $pdf->AddPage();
        $pdf->setBookmark($heading);
        $pdf->writeHTML($html);
    }

    private function writeSubchapter($pdf, $subchapter, $index)
    {
        $heading = sprintf("%s. %s", join(".", $index), $subchapter["title"]);
        $html = "<h3>$heading</h3>";
        $pdf->setBookmark($heading);
        $pdf->writeHTML($html);
    }

    private function writeSection($pdf, $section, $index)
    {
        $heading = sprintf("%s. %s", join(".", $index), $section["title"]);
        $html = "<h4>$heading</h4>";
        $pdf->setBookmark($heading);
        $pdf->writeHTML($html);
    }

    private function writeBlock($pdf, $block, $index)
    {
        $html = sprintf(
            "<h5>%s. Block (%s)</h5>",
            join(".", $index),
            $block["type"]
        );
        $pdf->writeHTML($html);

        $block["uiBlock"]->exportBlockIntoPdf($pdf);
    }

    private function getGrouped($cid)
    {
        $grouped = array_reduce(
            Block::findBySQL("seminar_id = ? ORDER BY id, position", [$cid]),
            function ($memo, $item) {
                $arr = $item->toArray();
                if (!$item->isStructuralBlock()) {
                    $arr["isBlock"] = true;
                    $ui_block = $this->plugin
                        ->getBlockFactory()
                        ->makeBlock($item);
                    $arr["uiBlock"] = $ui_block;
                }
                if ("Section" == $item->type) {
                    $ui_block = $this->plugin
                        ->getBlockFactory()
                        ->makeBlock($item);
                    $arr["icon"] = $ui_block->icon;
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
            case "Courseware":
                $sub_element = "Chapter";
                break;
            case "Chapter":
                $sub_element = "Subchapter";
                break;
            case "Subchapter":
                $sub_element = "Section";
                break;
            case "Section":
                $sub_element = "Block";
                break;
            case "Block":
            default:
        }

        return $sub_element;
    }

    private function buildTree($grouped, &$root)
    {
        $this->addChildren($grouped, $root);
        if ("Section" !== $root["type"]) {
            if (!empty($root["children"])) {
                foreach ($root["children"] as &$child) {
                    $this->buildTree($grouped, $child);
                }
            }
        } else {
            $root["children"] = $this->addChildren($grouped, $root);
        }
    }

    private function addChildren($grouped, &$parent)
    {
        $parent["children"] = $grouped[$parent["id"]];
        if (null != $parent["children"]) {
            usort($parent["children"], function ($a, $b) {
                return $a["position"] - $b["position"];
            });
        }

        return $parent["children"];
    }
}
