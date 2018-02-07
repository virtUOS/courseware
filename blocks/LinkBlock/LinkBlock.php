<?php
namespace Mooc\UI\LinkBlock;

use Mooc\UI\Block;

class LinkBlock extends Block 
{
    const NAME = 'Link';

    public function initialize()
    {
        $this->defineField('link_type', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('link_target', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('link_title', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {   
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }

        if ($this->link_type == "internal") {
            $link_id = $this->getTargetId($this->link_target);
        }

        if ($this->link_type == "external") {
            $link_href = $this->link_target;
        }

        $this->setGrade(1.0);

        return array_merge($this->getAttrArray(), array('link_id' => $link_id, 'link_href' => $link_href));
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        if (strpos($this->getModel()->parent->title, "AsideSection") > -1) {
            return $this->getAttrArray();
        }
        $inthischapter = $this->getThisChapterSiblings();
        $inotherchapters= $this->getOtherSubchapters();

        return array_merge($this->getAttrArray(), array(
            'inthischapter' => $inthischapter, 
            'inotherchapters' => $inotherchapters,
            'hasinternal' => true, 
            'hasnext'   => $this->getTargetId("next") != null,
            'hasprev'   => $this->getTargetId("prev") != null
        ));
    }

    private function getAttrArray() 
    {
        return array(
            'link_type'   => $this->link_type,
            'link_target' => $this->link_target,
            'link_title'  => $this->link_title
        );
    }

    private function getTargetId($target)
    {
        $id = '';
        $section = $this->getModel()->parent;
        $subchapter = $section->parent;
        $chapter = $subchapter->parent;
        $courseware = $this->container['current_courseware'];

        if ($target == 'next') {
            $id = $courseware->getNeighborSections($section)['next']['id'];
        }

        if ($target == 'prev') {
            $id = $courseware->getNeighborSections($section)['prev']['id'];
        }

        if (strpos($target, 'sibling') > -1) {
            $num = (int)substr($target, 7);
            $id = $this->getModel()->parent->parent->parent->children[$num]['id'];
        }

        if (strpos($target, 'other') > -1) {
            $chapter_pos = substr($target, 5);
            $chapter_pos = (int)strtok($chapter_pos,'_cpos');
            $subchapter_pos = (int)substr($target, strpos($target, '_item') + 5);

            $thischapter = $this->getModel()->parent->parent->parent;
            $allchapters = $thischapter->parent->children;
            $i = 0; $this_chapter_pos = "";
            foreach($allchapters as $chapter) {
                if($thischapter->id == $chapter->id) {
                    $this_chapter_pos = $i;
                }
                $i++;
            }

            $chatper = $allchapters[$this_chapter_pos + $chapter_pos];
            $id = $chatper->children[$subchapter_pos]['id'];
        }

        return $id;
    }

    private function getThisChapterSiblings()
    {
        $inthischapter = array();
        $chapter = $this->getModel()->parent->parent->parent;
        $children = $chapter->children;
        $i = 0;
        foreach ($children as $sibling) {
            array_push($inthischapter, array("value" => "sibling".$i, "title" => $sibling->title));
            $i++;
        }

        return $inthischapter;
    }

    private function getOtherSubchapters()
    {
        $inotherchapters = array();
        $thischapter = $this->getModel()->parent->parent->parent;
        $allchapters = $thischapter->parent->children;
        $i = 0; $this_chapter_pos = '';

        foreach($allchapters as $chapter) {
            if($thischapter->id == $chapter->id) {
                $this_chapter_pos = $i;
            }
            $i++;
        }

        foreach($allchapters as $key => $chapter) {
            if ($key == $this_chapter_pos) { 
                continue;
            }
            $relativ_chapter_pos = $key - $this_chapter_pos;
            $subchapters = $chapter->children;
            $i = 0;
            foreach ($subchapters as $subchapter) {
                array_push($inotherchapters, array(
                    'value' => 'other_cpos'.$relativ_chapter_pos.'_item'.$i, 
                    'title' => $chapter->title.' -> '.$subchapter->title
                ));
                $i++;
            }
        }

        return $inotherchapters;
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['link_type'])) {
            $this->link_type = (string) $data['link_type'];
        } 
        if (isset ($data['link_target'])) {
            $this->link_target = str_replace('http://', '', \STUDIP\Markup::purifyHtml((string) $data['link_target']));
        } 
        if (isset ($data['link_title'])) {
            $this->link_title = \STUDIP\Markup::purifyHtml((string) $data['link_title']);
        } 

        return;
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/link/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/link/link-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['link_type'])) {
            $this->link_type = $properties['link_type'];
        }
        if (isset($properties['link_target'])) {
            $this->link_target = $properties['link_target'];
        }
        if (isset($properties['link_title'])) {
            $this->link_title = $properties['link_title'];
        }

        $this->save();
    }
}
