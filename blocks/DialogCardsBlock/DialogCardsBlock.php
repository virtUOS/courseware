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
                $file_ref = new \FileRef($card->front_img_file_id);
                $file = new \File($file_ref->file_id);

                array_push( $files, array (
                    'id' => $file_ref->id,
                    'name' => $file_ref->name,
                    'description' => $file_ref->description,
                    'filename' => $file->name,
                    'filesize' => $file->size,
                    'url' => $file->getURL(),
                    'path' => $file->getPath()
                ));
            }
            if (!$card->back_external_file) {
                $file_ref = new \FileRef($card->back_img_file_id);
                $file = new \File($file_ref->file_id);

                array_push( $files, array (
                    'id' => $file_ref->id,
                    'name' => $file_ref->name,
                    'description' => $file_ref->description,
                    'filename' => $file->name,
                    'filesize' => $file->size,
                    'url' => $file->getURL(),
                    'path' => $file->getPath()
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
                    $card->front_img = $file->getDownloadURL();
                }
                if ($card->back_img_file_name == $file->name) {
                    $card->back_img_file_id = $file->id;
                    $card->back_img = $file->getDownloadURL();
                }
            }
            $cards[$key] = $card;
        }
        $this->dialogcards_content = json_encode($cards);

        $this->save();
    }
}
