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
        $this->defineField('ba_before', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('ba_after', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $this->setGrade(1.0);
        $ba_img_before = json_decode($this->ba_before)->url;
        $ba_img_after = json_decode($this->ba_after)->url;
        $ba_enable = (($ba_img_before != '') && ($ba_img_after != '')) ? true : false;

        return array(
            'beforeafter_img_before' => $ba_img_before,
            'beforeafter_img_after' => $ba_img_after,
            'ba_enable' => $ba_enable
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $before = json_decode($this->ba_before);
        $after = json_decode($this->ba_after);
        $ba_img_before_external = json_decode($this->ba_before)->source == 'url' ? true : false;
        $ba_img_after_external = json_decode($this->ba_after)->source == 'url' ? true : false;

        return array_merge($this->getAttrArray(), array(
            'image_files' => $this->showFiles(),
            'img_before' => $before->url,
            'img_after' => $after->url,
            'file_id_before' => $before->file_id,
            'file_id_after' => $after->file_id,
            'img_before_external' => $ba_img_before_external,
            'img_after_external' => $ba_img_after_external
        ));
    }

    private function getAttrArray() 
    {
        return array(
            'ba_before' => $this->ba_before,
            'ba_after' => $this->ba_after
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['ba_before'])) {
            $this->ba_before = \STUDIP\Markup::purifyHtml((string) $data['ba_before']);
        } 
        if (isset ($data['ba_after'])) {
            $this->ba_after = \STUDIP\Markup::purifyHtml((string) $data['ba_after']);
        } 

        return;
    }

    public function exportProperties()
    { 
       return array(
            'ba_before' => $this->ba_before,
            'ba_after' => $this->ba_after
            );
    }

    public function getFiles()
    {
        $ba_files = [];
        $before = json_decode($this->ba_before);
        $after = json_decode($this->ba_after);
        
        if ($before->source == 'file') {
            array_push($ba_files, $before->file_id);
        }
        if ($after->source == 'file') {
            array_push($ba_files, $after->file_id);
        }

        foreach ($ba_files as $ba_file){
            $file_ref = new \FileRef($ba_file);
            $file = new \File($file_ref->file_id);

            $files[] = array(
                'id' => $ba_file,
                'name' => $file_ref->name,
                'description' => $file_ref->description,
                'filename' => $file->name,
                'filesize' => $file->size,
                'url' => $file->getURL(),
                'path' => $file->getPath()
            );
        }

        return $files;
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
        if (isset($properties['ba_before'])) {
            $this->ba_before = $properties['ba_before'];
        }
        if (isset($properties['ba_after'])) {
            $this->ba_after = $properties['ba_after'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        $ba_before = json_decode($this->ba_before);
        $ba_after = json_decode($this->ba_after);

        foreach($files as $file){
            if(($ba_after->file_name == $file->name) && ($ba_after->source == 'file')) {
                $ba_after->file_id = $file->id;
                $file_ref_after = new \FileRef($ba_after->file_id);
                $ba_after->url = $file_ref_after->download_url;
            }
            if (($ba_before->file_name == $file->name) && ($ba_before->source == 'file')) {
                $ba_before->file_id = $file->id;
                $file_ref_before = new \FileRef($ba_before->file_id);
                $ba_before->url = $file_ref_before->download_url;
            }
        }

        $this->ba_before = json_encode($ba_before);
        $this->ba_after = json_encode($ba_after);

        $this->save();
    }

    private function showFiles()
    {
        $filesarray = array();
        $folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        
        foreach ($folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if ($ref->isImage())  {
                    $filesarray[] = $ref;
                }
            }
        }

        return $filesarray;
    }
}
