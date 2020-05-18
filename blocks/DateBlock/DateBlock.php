<?php
namespace Mooc\UI\DateBlock;

use Mooc\UI\Block;

class DateBlock extends Block 
{
    const NAME = 'Termin';
    const BLOCK_CLASS = 'layout';
    const DESCRIPTION = 'Zeigt einen Termin oder Countdown an';

    public function initialize()
    {
        $this->defineField('date_content', \Mooc\SCOPE_BLOCK, ''); //JSON
    }

    public function student_view()
    {
        $this->setGrade(1.0);

        return array('date_content' => \STUDIP\Markup::purifyHtml($this->date_content));
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array('date_content' => $this->date_content);
    }

    public function preview_view()
    {
        $date_content =  json_decode($this->date_content, true);
        return array(
            'date_title' => $date_content['title'],
            'date_date' => $date_content['date'],
            'date_time' => $date_content['time']
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['date_content'])) {
            $this->date_content = (string) $data['date_content'];
        } 

        return;
    }

    private function getAttrArray()
    {
        return array('date_content' => $this->date_content);
    }

    public function exportProperties()
    {
       return array('date_content' => $this->date_content);
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/date/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/date/date-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['date_content'])) {
            $this->date_content = $properties['date_content'];
        }

        $this->save();
    }
}
