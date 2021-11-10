<?php
namespace Mooc\UI\ChartBlock;

use Mooc\UI\Block;

class ChartBlock extends Block
{
    const NAME = 'Diagramm';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Stellt Datensätze in einem Diagramm dar';

    public function initialize()
    {
        $this->defineField('chart_content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('chart_label', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('chart_type', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $this->setGrade(1.0);

        return array_merge($this->getAttrArray(), array());
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getAttrArray(), array());
    }

    private function getAttrArray()
    {
        return array(
            'chart_content' => $this->chart_content,
            'chart_label' => $this->chart_label,
            'chart_type' => $this->chart_type
        );
    }

    public function preview_view()
    {
        $array = $this->getAttrArray();

        $array['chart_type'] = $this->getChartTypeName($array['chart_type']);

        return $array;
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['chart_content'])) {
            $this->chart_content = (string) $data['chart_content'];
        }
        if (isset ($data['chart_type'])) {
            $this->chart_type = (string) $data['chart_type'];
        }
        if (isset ($data['chart_label'])) {
            $this->chart_label = \STUDIP\Markup::purifyHtml((string) $data['chart_label']);
        }

        return;
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function getHtmlExportData()
    {
        $array = $this->getAttrArray();
        $array['chart_content'] = json_decode($array['chart_content']);

        return $array;
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/chart/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/chart/chart-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['chart_content'])) {
            $this->chart_content = $properties['chart_content'];
        }
        if (isset($properties['chart_type'])) {
            $this->chart_type = $properties['chart_type'];
        }
        if (isset($properties['chart_label'])) {
            $this->chart_label = $properties['chart_label'];
        }

        $this->save();
    }

    private function getChartTypeName($type)
    {
        switch ($type) {
            case 'bar':
                $name = 'Säulendiagramm';
                break;
            case 'horizontalBar':
                $name = 'Balkendiagramm';
                break;
            case 'pie':
                $name = 'Kreisdiagramm';
                break;
            case 'doughnut':
                $name = 'Ringdiagramm';
                break;
            case 'polarArea':
                $name = 'Polardiagramm';
                break;
            case 'line':
                $name = 'Liniendiagramm';
                break;
            default:
                $name = '';
        }

        return $name;
    }
}
