<?php
namespace Mooc\UI\KeyPointBlock;

use Courseware\PdfExportHelperTrait;
use Mooc\UI\Block;

class KeyPointBlock extends Block
{
    use PdfExportHelperTrait;

    const NAME = 'Merksatz';
    const BLOCK_CLASS = 'layout';
    const DESCRIPTION = 'Erzeugt einen Merksatz mit Icon und Rahmen';

    public function initialize()
    {
        $this->defineField('keypoint_content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('keypoint_color', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('keypoint_icon', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        if(($this->keypoint_content == '')) {
            return array('keypoint' => false, 'empty' => true, 'isAuthor' => $this->getUpdateAuthorization());
        }
        $this->setGrade(1.0);

        return array_merge($this->getAttrArray());
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getAttrArray());
    }

    private function getAttrArray()
    {
        return array(
            'keypoint' => true,
            'keypoint_content' => $this->keypoint_content,
            'keypoint_color'   => $this->keypoint_color,
            'keypoint_icon'    => $this->keypoint_icon
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['keypoint_content'])) {
            $this->keypoint_content = \STUDIP\Markup::purifyHtml((string) $data['keypoint_content']);
        }
        if (isset ($data['keypoint_color'])) {
            $this->keypoint_color = (string) $data['keypoint_color'];
        }
        if (isset ($data['keypoint_icon'])) {
            $this->keypoint_icon = (string) $data['keypoint_icon'];
        }

        return;
    }

    public function preview_view()
    {
        return array('keypoint_content' => substr($this->keypoint_content, 0, 160));
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function pdfexport_view()
    {
        $data = $this->student_view();
        $data['keypoint_img'] = $this->getRasteredIcon($data['keypoint_color'], $data['keypoint_icon'], 100);
        $data['keypoint_hexcolor'] = $this->getHexColor($data['keypoint_color']);

        return $data;
    }

    private function getHexColor($color)
    {
        $hexcolor = '#000';
        switch($color) {
            case 'red':
                $hexcolor = '#d60000';
                break;
            case 'green':
                $hexcolor = '#339d41';
                break;
            case 'grey':
                $hexcolor = '#3c454e';
                break;
            case 'blue':
                $hexcolor = '#28497c';
                break;
            case 'yellow':
                $hexcolor = '#ffdb33';
                break;

        }

        return $hexcolor;
    }

    public function getHtmlExportData()
    {
        return $this->getAttrArray();
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/keypoint/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/keypoint/keypoint-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['keypoint_content'])) {
            $this->keypoint_content = $properties['keypoint_content'];
        }
        if (isset($properties['keypoint_color'])) {
            $this->keypoint_color = $properties['keypoint_color'];
        }
        if (isset($properties['keypoint_icon'])) {
            $this->keypoint_icon = $properties['keypoint_icon'];
        }

        $this->save();
    }
}
