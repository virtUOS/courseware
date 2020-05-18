<?php
namespace Mooc\UI\TypewriterBlock;

use Mooc\UI\Block;

class TypewriterBlock extends Block 
{
    const NAME = 'Schreibmaschine';
    const BLOCK_CLASS = 'layout';
    const DESCRIPTION = 'Der Text erscheint Zeichen für Zeichen';

    public function initialize()
    {
        $this->defineField('typewriter_json', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $this->setGrade(1.0);

        return array('typewriter_json' => \STUDIP\Markup::purifyHtml($this->typewriter_json));
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array('typewriter_json' => $this->typewriter_json);
    }

    public function preview_view()
    {
        $content = json_decode($this->typewriter_json, true)['content'];
        if (strlen($content) > 240){
            $content = substr($content, 0, 240).'…';
        }

        return array('content' => $content);
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['typewriter_json'])) {
            $this->typewriter_json = (string) $data['typewriter_json'];
        } 

        return;
    }

    public function exportProperties()
    {
       return array('typewriter_json' => $this->typewriter_json);
    }

    public function getHtmlExportData()
    {
        return $this->exportProperties();
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/typewriter/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/typewriter/typewriter-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['typewriter_json'])) {
            $this->typewriter_json = $properties['typewriter_json'];
        }

        $this->save();
    }
}
