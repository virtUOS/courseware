<?php
namespace Mooc\UI\CodeBlock;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

class CodeBlock extends Block
{
    const NAME = 'Quellcode';

    public function initialize()
    {
        $this->defineField('code_content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('code_lang', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
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
            'code_content' => $this->code_content,
            'code_lang' => $this->code_lang
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset ($data['code_content'])) {
            $this->code_content = (string) $data['code_content'];
        } 
        if (isset ($data['code_lang'])) {
            $this->code_lang = (string) $data['code_lang'];
        } 

        return;
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['code_content'])) {
            $this->code_content = $properties['code_content'];
        }
        if (isset($properties['code_lang'])) {
            $this->code_lang = $properties['code_lang'];
        }

        $this->save();
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/code/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/code/code-1.0.xsd';
    }

}
