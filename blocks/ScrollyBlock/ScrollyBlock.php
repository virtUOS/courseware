<?php

namespace Mooc\UI\ScrollyBlock;

use Mooc\UI\Block;

class ScrollyBlock extends Block
{
    const NAME = 'Scrollytelling';
    const BLOCK_CLASS = 'scrolly';
    const DESCRIPTION = 'Formatiert die Blöcke auf einer Scrollytelling-Seite';

    public function initialize()
    {
        $this->defineField('scrolly_block_style', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $this->setGrade(1.0);

        return array_merge($this->getAttrArray(), array());
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getBlocksInSection(), $this->getAttrArray());
    }

    public function preview_view()
    {

        return;
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset($data['scrolly_block_style'])) {
            $this->scrolly_block_style = (string) $data['scrolly_block_style'];
        }

        return;
    }

    private function getBlocksInSection()
    {
        $children = $this->getModel()->parent->children;
        $blocks = array();
        foreach($children as $child)
        {
            if (!in_array($child["type"], array("ScrollyBlock"))){
                $className = '\Mooc\UI\\'.$child["type"].'\\'.$child["type"];
                $blocks[] = array('blockid' =>$child->id, 'blocktype'=> $child->type, 'blockname' => _cw(constant($className.'::NAME')));
            }
        }

        return array('blocks' => $blocks);
    }

    private function getAttrArray()
    {
        return array(
            'scrolly_block_style' => $this->scrolly_block_style
        );
    }

    public function exportProperties()
    {
       return array_merge(
            $this->getAttrArray(),
            array('scrolly_block_style' => $this->scrolly_block_style)
        );
    }

    public function pdfexport_view()
    {
        return array();
    }

    public function getHtmlExportData()
    {
        return $this->getAttrArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/scrolly/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/scrolly/scrolly-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['scrolly_block_style'])) {
            $this->scrolly_block_style = $properties['scrolly_block_style'];
        }

        $this->save();
    }

}
