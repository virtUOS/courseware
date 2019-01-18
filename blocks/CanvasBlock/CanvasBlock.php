<?php

namespace Mooc\UI\CanvasBlock;

use Mooc\UI\Block;

class CanvasBlock extends Block
{
    const NAME = 'Canvas';
    const BLOCK_CLASS = 'interaction';
    const DESCRIPTION = 'Draw something awesome';

    public function initialize()
    {
        $this->defineField('canvas_content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('canvas_mode', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $content = json_decode($this->canvas_content);

        return array_merge(
            $this->getAttrArray(),
            array('bgimage'=> $content->image)
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge(
            $this->getAttrArray(), 
            array()
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset($data['canvas_content'])) {
            $this->canvas_content = $data['canvas_content'];
        }
        if (isset($data['canvas_mode'])) {
            $this->canvas_mode = $data['canvas_mode'];
        }

        return;
    }

    private function showFiles()
    {
        $filesarray = array();
        $folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        $user_folders =  \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $folders = array_merge($folders, $user_folders);

        foreach ($folders as $folder) {
            $file_refs = \FileRef::findBySQL('folder_id = ?', array($folder->id));
            foreach($file_refs as $ref){
                if (($ref->isImage()) && (!$ref->isLink())) {
                    $filesarray[] = $ref;
                }
            }
        }

        return $filesarray;
    }

    private function getAttrArray()
    {
        return array(
            'canvas_content' => $this->canvas_content,
            'canvas_mode' => $this->canvas_mode,
        );
    }

    public function exportProperties()
    {
       return array_merge(
            $this->getAttrArray(),
            array()
        );
    }

    public function getFiles()
    {
        
        //if ($this->audio_source != 'cw') {
            //return;
        //}
        //if ($this->audio_id == '') {
            //return;
        //}
        //$file_ref = new \FileRef($this->audio_id);
        //$file = new \File($file_ref->file_id);
        
        //$files[] = array(
            //'id' => $this->audio_id,
            //'name' => $file_ref->name,
            //'description' => $file_ref->description,
            //'filename' => $file->name,
            //'filesize' => $file->size,
            //'url' => $file->getURL(),
            //'path' => $file->getPath()
        //);

        //return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/canvas/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/canvas/canvas-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['canvas_content'])) {
            $this->canvas_content = $properties['canvas_content'];
        }
        if (isset($properties['canvas_mode'])) {
            $this->canvas_mode = $properties['canvas_mode'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        //foreach($files as $file){
            //if ($file->name == '') {
                //continue;
            //}
            //if($this->audio_file_name == $file->name) {
                //$this->audio_id = $file->id;
                //if ($this->audio_source == 'cw') {
                    //$this->audio_file = $file->getDownloadURL();
                    //$this->save();
                //}
            //}
        //}
    }
}
