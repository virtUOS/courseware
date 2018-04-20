<?php
namespace Mooc\UI\DownloadBlock;

use Mooc\UI\Block;

class DownloadBlock extends Block 
{
    const NAME = 'Download';

    public function initialize()
    {
        $this->defineField('file', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_name', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('folder_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_title', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_info', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_success', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $file = \FileRef::find($this->file_id);
        if ($file) { 
            $url = $file->getDownloadURL('force');
            $access = ($file->terms_of_use->download_condition == 0) ? true : false;
        } else { 
            $url = "";
            $access = true;
        }

        return array_merge(
            $this->getAttrArray(), 
            array('confirmed' => !! $this->getProgress()->grade, 
                  'url' => $url,
                  'download_access' => $access
            )
        );
    }

    public function author_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $this->authorizeUpdate();
        $allfiles = $this->showFiles($this->folder_id);
        $folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        return array_merge($this->getAttrArray(), array('allfiles' => $allfiles, 'folders' => $folders));
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset ($data['file'])) {
            $this->file = (string) $data['file'];
            $this->file_id = (string) $data['file_id'];
            $this->file_name = (string) $data['file_name'];
            $this->folder_id = (string) $data['folder_id'];
            $this->download_title = (string) $data['download_title'];
            $this->download_info = (string) $data['download_info'];
            $this->download_success = (string) $data['download_success'];
        }

        return;
    }

    public function setfolder_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset ($data['folder_id'])) {

            return $this->showFiles($data['folder_id']);
        }

        return false;
    }

    public function download_handler($data)
    {
        $this->setGrade(1.0);
        return ;
    }

    private function showFiles($folder_id)
    {
        $response = \FileRef::findBySQL('folder_id = ?', array($folder_id));
        $filesarray = array();
        foreach ($response as $item) {
            $filesarray[] = array("id" => $item->id, "name" => $item->name);
        }
        return $filesarray;
    }

    private function getAttrArray() 
    {
        return array(
            'file' => $this->file, 
            'file_id' => $this->file_id, 
            'file_name' => $this->file_name, 
            'folder_id' => $this->folder_id,
            'download_title' => $this->download_title,
            'download_info' => $this->download_info,
            'download_success' => $this->download_success
        );
    }

    public function exportProperties()
    {
       return array(
            'file' => $this->file,
            'file_id' => $this->file_id,
            'file_name' => $this->file_name,
            'folder_id' => $this->folder_id,
            'download_title' => $this->download_title,
            'download_info' => $this->download_info,
            'download_success' => $this->download_success
       );
    }

    public function getFiles()
    {
        $file_ref = new \FileRef($this->file_id);
        $file = new \File($file_ref->file_id);
        
        $files[] = array(
            'id' => $this->file_id,
            'name' => $file_ref->name,
            'description' => $file_ref->description,
            'filename' => $file->name,
            'filesize' => $file->size,
            'url' => $file->getURL(),
            'path' => $file->getPath()
        );

        return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/download/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/download/download-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['file'])) {
            $this->file = $properties['file'];
        }

        if (isset($properties['file_name'])) {
            $this->file_name = $properties['file_name'];
        }

        if (isset($properties['download_title'])) {
            $this->download_title = $properties['download_title'];
        }

        if (isset($properties['download_info'])) {
            $this->download_info = $properties['download_info'];
        }

        if (isset($properties['download_success'])) {
            $this->download_success = $properties['download_success'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        foreach($files as $file){
            if($this->file_name == $file->name) {
                $this->file_id = $file->id;
                $this->save();
            }
        }

    }

}
