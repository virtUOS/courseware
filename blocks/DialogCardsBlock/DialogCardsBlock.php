<?php
namespace Mooc\UI\DialogCardsBlock;

use Mooc\UI\Block;

class DialogCardsBlock extends Block 
{
    const NAME = 'Lernkarten';
    const BLOCK_CLASS = 'interaction';
    const DESCRIPTION = 'Karten zum Umdrehen, auf beiden Seiten lässt sich ein Bild und Text darstellen';

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
        return array_merge($this->getAttrArray(), array(
            'cards' => json_decode($this->dialogcards_content),
            'image_files' => $this->showFiles()
        ));
    }

    private function getAttrArray() 
    {
        return array(
            'dialogcards_content' => $this->dialogcards_content
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

    public function getFiles()
    {
        $cards = json_decode($this->dialogcards_content);
        $files = array();

        foreach ($cards as $card) {
            if (!$card->front_external_file) {
                $document = new \StudipDocument($card->front_img_file_id);
                array_push( $files, array (
                    'id' => $card->front_img_file_id,
                    'name' => $document->name,
                    'description' => $document->description,
                    'filename' => $document->filename,
                    'filesize' => $document->filesize,
                    'url' => $document->url,
                    'path' => get_upload_file_path($card->front_img_file_id)
                ));
            }
            if (!$card->back_external_file) {
                $document = new \StudipDocument($card->back_img_file_id);
                array_push( $files, array (
                    'id' => $card->back_img_file_id,
                    'name' => $document->name,
                    'description' => $document->description,
                    'filename' => $document->filename,
                    'filesize' => $document->filesize,
                    'url' => $document->url,
                    'path' => get_upload_file_path($card->back_img_file_id)
                ));
            }
        }

        return $files;
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

    public function importContents($contents, array $files)
    {
        $cards = json_decode($this->dialogcards_content);
        foreach ($cards as $key => $card) {
            foreach($files as $file){
                if ($card->front_img_file_name == $file->name) {
                    $card->front_img_file_id = $file->id;
                    $document = \StudipDocument::find($file->id);
                    $card->front_img = \URLHelper::getURL( 'sendfile.php',  array('type'=>'0', 'file_id' => $document->id, 'file_name' => $document->name) );
                }
                if ($card->back_img_file_name == $file->name) {
                    $card->back_img_file_id = $file->id;
                    $document = \StudipDocument::find($file->id);
                    $card->back_img = \URLHelper::getURL( 'sendfile.php',  array('type'=>'0', 'file_id' => $document->id, 'file_name' => $document->name) );
                }
            }
            $cards[$key] = $card;
        }
        $this->dialogcards_content = json_encode($cards);

        $this->save();
    }
}
