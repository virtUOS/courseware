<?php
namespace Mooc\UI\DialogCardsBlock;

use Mooc\UI\Block;

class DialogCardsBlock extends Block 
{
    const NAME = 'Lernkarten';
    const BLOCK_CLASS = 'interaction';
    const DESCRIPTION = 'flip cards in a carusel';

    public function initialize()
    {
        $this->defineField('dialogcards_content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {   
        $this->setGrade(1.0);
        $cards = json_decode($this->dialogcards_content);

        return array_merge($this->getAttrArray(), array(
            'cards' => json_decode($this->dialogcards_content)
        ));
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getAttrArray(), array('cards' => json_decode($this->dialogcards_content)));
    }

    private function getAttrArray() 
    {
        return array(
            'dialogcards_content' => $this->dialogcards_content
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['dialogcards_content'])) {
            $this->dialogcards_content = (string) $data['dialogcards_content'];
        } 

        return;
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/dialogcards/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/dialogcards/dialogcards-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['dialogcards_content'])) {
            $this->dialogcards_content = $properties['dialogcards_content'];
        }

        $this->save();
    }
}
