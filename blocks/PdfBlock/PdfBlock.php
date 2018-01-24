<?php
namespace Mooc\UI\PdfBlock;

use Mooc\UI\Block;

class PdfBlock extends Block 
{
    const NAME = 'PDF mit Vorschau';

    public function initialize()
    {
        $this->defineField('pdf_file', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('pdf_filename', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('pdf_file_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('pdf_title', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {   
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        
        $document = \StudipDocument::find($this->pdf_file_id);
        if ($document) {
            $access = $document->checkAccess($this->container['current_user_id']);
        }
        $this->setGrade(1.0);

        return array_merge($this->getAttrArray(), array('access' => $access));
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getAttrArray(), array('pdf_files' => $this->showFiles()));
    }

    private function getAttrArray() 
    {
        return array(
            'pdf_file'      => $this->pdf_file,
            'pdf_filename'  => $this->pdf_filename,
            'pdf_file_id'   => $this->pdf_file_id,
            'pdf_title'     => $this->pdf_title
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
        if (isset ($data['pdf_filename'])) {
            $this->pdf_filename = $data['pdf_filename'];
        }
        if (isset ($data['pdf_file_id'])) {
            $this->pdf_file_id = $data['pdf_file_id'];
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

    public function getFiles()
    {
        $document = new \StudipDocument($this->pdf_file_id);
        $files[] = array(
            'id'            => $this->pdf_file_id,
            'name'          => $document->name,
            'description'   => $document->description,
            'filename'      => $document->filename,
            'filesize'      => $document->filesize,
            'url'           => $document->url,
            'path'          => get_upload_file_path($this->pdf_file_id)
        );

        return $files;
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
        if (isset($properties['pdf_title'])) {
            $this->pdf_title = $properties['pdf_title'];
        }
        if (isset($properties['pdf_filename'])) {
            $this->pdf_filename = $properties['pdf_filename'];
        }
        $this->setFileId($this->pdf_filename);
        $this->save();
    }

    private function setFileId($file_name)
    {
        $cid = $this->container['cid'];
        $document = current(\StudipDocument::findBySQL('filename = ? AND seminar_id = ?', array($file_name, $cid)));
        $this->pdf_file_id = $document->dokument_id;
        if ($document->url == "") {
            $this->pdf_file = "../../sendfile.php?type=0&file_id=".$document->dokument_id."&file_name=".$file_name;
        } else {
            $this->pdf_file = "../../sendfile.php?type=6&file_id=".$document->dokument_id."&file_name=".$file_name;
        }
        return;
    }

    public function importContents($contents, array $files)
    {
        $file = reset($files);
        if (($file->id == $this->pdf_file_id)) {
            $this->save();
        }
    }
}
