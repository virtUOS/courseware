<?php
namespace Mooc\UI\TypewriterBlock;

use Mooc\UI\Block;

class TypewriterBlock extends Block 
{
    const NAME = 'Typewriter';
    const BLOCK_CLASS = 'layout';
    const DESCRIPTION = 'Typewriter';

    public function initialize()
    {
        $this->defineField('typewriter_content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $this->setGrade(1.0);

        return array('typewriter_content' => \STUDIP\Markup::purifyHtml($this->typewriter_content));
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array('typewriter_content' => $this->typewriter_content);
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['typewriter_content'])) {
            $this->typewriter_content = (string) $data['typewriter_content'];
        } 

        return;
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
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
        if (isset($properties['typewriter_content'])) {
            $this->typewriter_content = $properties['typewriter_content'];
        }

        $this->save();
    }
}
