<?php
namespace Mooc\UI\BeforeAfterBlock;

use Mooc\UI\Block;

class BeforeAfterBlock extends Block 
{
    const NAME = 'Bildvergleich';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Vergleicht zwei Bilder mit einem Schieberegler';

    public function initialize()
    {
        $this->defineField('beforeafter_img_before', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('beforeafter_img_after', \Mooc\SCOPE_BLOCK, '');
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
            'beforeafter_img_before' => $this->beforeafter_img_before,
            'beforeafter_img_after' => $this->beforeafter_img_after
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['beforeafter_img_before'])) {
            $this->beforeafter_img_before = (string) $data['beforeafter_img_before'];
        } 
        if (isset ($data['beforeafter_img_after'])) {
            $this->beforeafter_img_after = (string) $data['beforeafter_img_after'];
        } 

        return;
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/beforeafter/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/beforeafter/beforeafter-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['beforeafter_img_before'])) {
            $this->beforeafter_img_before = $properties['beforeafter_img_before'];
        }
        if (isset($properties['beforeafter_img_after'])) {
            $this->beforeafter_img_after = $properties['beforeafter_img_after'];
        }

        $this->save();
    }
}
