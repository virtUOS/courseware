<?
namespace Mooc\UI\DownloadBlock;

use Mooc\UI\Block;

class DownloadBlock extends Block 
{
    const NAME = 'Download';

    function initialize()
    {
        $this->defineField('file', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_name', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('folder_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_title', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_info', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_success', \Mooc\SCOPE_BLOCK, '');
    }

    function student_view()
    {
        return array_merge($this->getAttrArray(), ['confirmed' => !! $this->getProgress()->grade]);
    }

    function author_view()
    {
        $this->authorizeUpdate();
        $allfiles = $this->showFiles($this->folder_id);
        return array_merge($this->getAttrArray(), ["allfiles" => $allfiles, "foldernames" => $this->getFolderNames()]);
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

        if (isset ($data['folder'])) {
            $folderId = $this->getFolderId((string) $data['folder']);
            return $this->showFiles($folderId);
        }

        return false;
    }

    function download_handler($data)
    {
        $this->setGrade(1.0);
        return ;
    }

    function getFolderId($foldername){
        $cid = $this->container['cid'];
        $db = \DBManager::get();
        $stmt = $db->prepare("SELECT folder_id FROM folder WHERE name = :foldername AND seminar_id = :cid");
        $stmt->bindParam(":foldername", $foldername);
        $stmt->bindParam(":cid", $cid);
        $stmt->execute();
        return $stmt->fetch()['folder_id'];
    }

    private function getFolderNames() {
        $cid = $this->container['cid'];
        $db = \DBManager::get();
        $stmt = $db->prepare("SELECT * FROM folder WHERE  seminar_id = :cid");
        $stmt->bindParam(":cid", $cid);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function showFiles($folderId, $filetype = "")
    {
        $db = \DBManager::get();
        $stmt = $db->prepare("SELECT * FROM `dokumente` WHERE `range_id` = :range_id
            ORDER BY `name`");
        $stmt->bindParam(":range_id", $folderId);
        $stmt->execute();
        $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $filesarray = array();
        
        foreach ($response as $item) {
            $filesarray[] = $item;
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
        $document = new \StudipDocument($this->file_id);
        $files[] = array (
            'id' => $this->file_id,
            'name' => $this->file,
            'description' => $document->description,
            'filename' => $document->filename,
            'filesize' => $document->filesize,
            'url' => $document->url,
            'path' => get_upload_file_path($this->file_id),
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

        $this->setFileId($this->file, $this->file_name);

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

    private function setFileId($file, $file_name)
    {
        $cid = $this->container['cid'];
        $document =  current(\StudipDocument::findBySQL('filename = ? AND name = ? AND seminar_id = ?', array($file_name, $file, $cid)));
        $this->file_id = $document->dokument_id;
        $this->folder_id = $document->range_id;
        return;
    }

    public function importContents($contents, array $files)
    {
        $file = reset($files);
        if ($file->id == $this->file_id) {
            $this->save();
        }
    }

}
