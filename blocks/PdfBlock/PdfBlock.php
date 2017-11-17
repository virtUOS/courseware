<?php
namespace Mooc\UI\PdfBlock;

use Mooc\UI\Block;

class PdfBlock extends Block 
{
    const NAME = 'PDF';

    public function initialize()
    {
        $this->defineField('pdf_file', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('pdf_title', \Mooc\SCOPE_BLOCK, '');
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

        return array_merge($this->getAttrArray(), array('pdf_files' => $this->showFiles()));
    }

    private function getAttrArray() 
    {
        return array(
            'pdf_file' => $this->pdf_file,
            'pdf_title' => $this->pdf_title
        );
    }
    
    private function showFiles()
    {
        $db = \DBManager::get();
        $stmt = $db->prepare('
            SELECT 
                * 
            FROM 
                dokumente 
            WHERE 
                seminar_id = :seminar_id
            ORDER BY 
                name
        ');
        $stmt->bindParam(':seminar_id', $this->container['cid']);
        $stmt->execute();
        $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $filesarray = array();
        foreach ($response as $item) {
            if ((strpos($item['filename'], 'pdf') > -1)) {
                if($item['url'] == "") {unset($item['url']);}
                $filesarray[] = $item;
            }
        }

        return $filesarray;
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['pdf_file'])) {
            $this->pdf_file = $data['pdf_file'];
        }
        if (isset ($data['pdf_title'])) {
            $this->pdf_title = $data['pdf_title'];
        }

        return;
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/pdf/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/pdf/pdf-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['pdf_file'])) {
            $this->pdf_file = $properties['pdf_file'];
        }
        if (isset($properties['pdf_title'])) {
            $this->pdf_title = $properties['pdf_title'];
        }

        $this->save();
    }
}
