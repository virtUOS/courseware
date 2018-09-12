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
            $document = new \StudipDocument($ba_file);
            $files[] = array(
                'id' => $ba_file,
                'name' => $document->name,
                'description' => $document->description,
                'filename' => $document->filename,
                'filesize' => $document->filesize,
                'url' => $document->url,
                'path' => get_upload_file_path($ba_file)
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
                $document = \StudipDocument::find($file->id);
                $ba_after->url = \URLHelper::getURL( 'sendfile.php',  array('type'=>'0', 'file_id' => $document->id, 'file_name' => $document->name) );
            }
            if (($ba_before->file_name == $file->name) && ($ba_before->source == 'file')) {
                $ba_before->file_id = $file->id;
                $document = \StudipDocument::find($file->id);
                $ba_before->url = \URLHelper::getURL( 'sendfile.php',  array('type'=>'0', 'file_id' => $document->id, 'file_name' => $document->name) );
            }
        }

        $this->ba_before = json_encode($ba_before);
        $this->ba_after = json_encode($ba_after);

        $this->save();
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
        $mimetypes = [
            'bmp', 'cod', 'ras', 'fif', 'gif', 'ief', 'jpeg', 
            'jpg', 'jpe', 'png', 'svg', 'tif', 'tiff', 'mcf', 
            'wbmp', 'fh4', 'fh5', 'fhc', 'ico', 'pnm', 'pbm',
            'pgm', 'ppm', 'rgb', 'xwd', 'xbm', 'xpm'
        ];
        foreach ($response as $item) {
            if(in_array(strtolower(substr($item['name'], -3)), $mimetypes) || in_array(strtolower(substr($item['name'], -4)), $mimetypes))
            {
                if (\StudipDocument::find($item['dokument_id'])->checkAccess($this->container['current_user_id'])) {
                    $document = \StudipDocument::find($item['dokument_id']);
                    $item['download_url'] = \URLHelper::getURL( 'sendfile.php',  array('type'=>'0', 'file_id' => $document->id, 'file_name' => $document->name) );
                    $filesarray[] = $item;
                }
            }
        }

        return $filesarray;
    }
}
