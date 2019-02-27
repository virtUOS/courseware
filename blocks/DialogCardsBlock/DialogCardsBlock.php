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
        $cards = json_decode($this->dialogcards_content, true);
        foreach($cards as &$card) {
            if ($card['front_img_file_id']) {
                $file_front = \FileRef::find($card['front_img_file_id']);
                $card['front_img'] = ($file_front->terms_of_use->fileIsDownloadable($file_front, false)) ? $file_front->getDownloadURL() : '';
            }
            if ($card['back_img_file_id']) {
                $file_back = \FileRef::find($card['back_img_file_id']);
                $card['back_img'] = ($file_back->terms_of_use->fileIsDownloadable($file_back, false)) ? $file_back->getDownloadURL() : '';
            }
        }

        return array_merge($this->getAttrArray(), array(
            'cards' => $cards
        ));
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        
        $cards = json_decode($this->dialogcards_content);

        $file_ids = array();
        foreach ($cards as $card) {
            if (!empty($card->front_img_file_id)) {
                array_push($file_ids, $card->front_img_file_id);
            }
            if (!empty($card->back_img_file_id)) {
                array_push($file_ids, $card->back_img_file_id);
            }
        }

        $files_arr = $this->showFiles($file_ids);

        $no_files = empty($files_arr['userfilesarray']) && empty($files_arr['coursefilesarray']) && empty($files_arr['other_user_files']) && empty($file_ids);

        return array_merge($this->getAttrArray(), array(
            'cards' => $cards,
            'no_files' => $no_files,
            'image_files_other' => $files_arr['other_user_files'],
            'image_files_user' => $files_arr['userfilesarray'],
            'image_files_course' => $files_arr['coursefilesarray']
        ));
    }

    public function preview_view()
    {
        return array('first_card' => json_decode($this->dialogcards_content, true)[0]);
    }

    private function getAttrArray() 
    {
        return array(
            'dialogcards_content' => $this->dialogcards_content
        );
    }

    private function showFiles($file_ids)
    {
        $coursefilesarray = array();
        $userfilesarray = array();
        $course_folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        $user_folders =  \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $other_user_files = array();

        foreach ($course_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink())) {
                    $coursefilesarray[] = $ref;
                }
                $key = array_search($ref->id, $file_ids);
                if($key > -1) {
                    unset ($file_ids[$key]);
                }
            }
        }

        foreach ($user_folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink())) {
                    $userfilesarray[] = $ref;
                }
                if(in_array($ref->id, $file_ids)) {
                    unset ($file_ids[$key]);
                }
            }
        }

        if (empty($file_ids)) {
            $other_user_files = false;
        } else {
            foreach ($file_ids as $id) {
                $file_ref = \FileRef::find($id);
                array_push($other_user_files, $file_ref);
            }
        }

        return array('coursefilesarray' => $coursefilesarray, 'userfilesarray' => $userfilesarray,  'other_user_files' => $other_user_files);
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
            if ((!$card->front_external_file) && (!empty($card->front_img_file_id))) {
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
            if ((!$card->back_external_file) && (!empty($card->back_img_file_id))) {
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
        $used_files = array();

        foreach ($cards as $key => $card) {
            foreach($files as $file){
                if($file->name == '') {
                    continue;
                }
                if ($card->front_img_file_name == $file->name) {
                    $card->front_img_file_id = $file->id;
                    $card->front_img = $file->getDownloadURL();
                    array_push($used_files, $file->id);
                }
                if ($card->back_img_file_name == $file->name) {
                    $card->back_img_file_id = $file->id;
                    $card->back_img = $file->getDownloadURL();
                    array_push($used_files, $file->id);
                }
            }
            $cards[$key] = $card;
        }
        $this->dialogcards_content = json_encode($cards);

        $this->save();
        return $used_files;
    }
}
