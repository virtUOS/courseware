<?
namespace Mooc\UI\BadgeBlock;

use Mooc\UI\Block;

class BadgeBlock extends Block 
{
    const NAME = 'Badge';

    function initialize()
    {
        $this->defineField('file', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_height', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_width', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('file_name', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('folder_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_title', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_info', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('download_success', \Mooc\SCOPE_BLOCK, '');
            
    }
    
    function submitBadge(){
         //courseware id
        global $user;
        
        //preceeding block completed?
        if ($this->getModel()->parent->hasUserCompleted($user->id)){
        //if ($this->getModel()->hasUserCompleted($user->id)){
           return true;
        } else return false;
    }
    
    function storeBadge(){
        global $user;
        
       //insert into mooc_badge user_id, sem_id, badge-block_id and date
        $values = array('user_id' => $user->id, 'sem_id' => $this->container['cid'], 'badge_block_id' => $this->id);
        $query = "SELECT * FROM `mooc_badges` WHERE `user_id` = :user_id AND `sem_id` = :sem_id AND `badge_block_id` = :badge_block_id" ;
	$statement = \DBManager::get()->prepare($query);
	$statement->execute($values);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (!$result){
            $values2 = array('user_id' => $user->id, 'sem_id' => $this->container['cid'], 'badge_block_id' => $this->id, 'date' => date_timestamp_get(date_create()));
            $query2 = "INSERT INTO `mooc_badges` (`user_id`, `sem_id`, `badge_block_id`, `mkdate`) VALUES (:user_id, :sem_id, :badge_block_id, :date)" ;
            $statement2 = \DBManager::get()->prepare($query2);
            $statement2->execute($values2);
        }
    }

    function student_view()
    {
        $this->setGrade(1.0);
        
        if($this->submitBadge()){
        
         $this->storeBadge(); 
         //$this->sendBadgeMail();
        }
        return array_merge($this->getAttrArray(), ['confirmed' => $this->submitBadge()]);
    }

    function author_view()
    {
        $this->authorizeUpdate();
        $this->setFolderId();
        $allfiles = $this->showFiles($this->folder_id);
        return array_merge($this->getAttrArray(), ["allfiles" => $allfiles]);
    }
    
      function sendBadgeMail(){
        
        global $user;
        $empfaenger = \User::find($user->id)->Email;
        
        $mailtext = '<html>
          

            <body>

            <h2>Teilnahmezertifikat für </h2>

            <p>Im Anhang finden Sie ein Teilnahmezertifikat für den/die Teilnehmer/in einer Onlineschulung</p>

            </body>
            </html>
            ';

            $betreff    = "Sie haben eine Auszeichnung erhalten!";
            $filename = $this->file_name;

            \messaging::sendSystemMessage($user->id, $betreff, $mailtext);
            /**
            $mail = new \StudipMail();
            return $mail->addRecipient($empfaenger)
                 ->setReplyToEmail('')
                 ->setSenderEmail('')
                 ->setSenderName('Stud.IP')
                 ->setSubject($betreff)
                 ->addStudipAttachment($attachment[$this->file_id])
                 ->setBodyHtml($mailtext)
                 ->setBodyHtml(strip_tags($mailtext))  
                 ->send();
            **/
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        
        if (isset ($data['file'])) {
            $this->file = (string) $data['file'];
            $this->file_id = (string) $data['file_id'];
            $this->file_name = (string) $data['file_name'];
            $this->file_width = (string) $data['file_width'];
            $this->file_height = (string) $data['file_height'];
            
            $this->download_title = (string) $data['download_title'];
            $this->download_info = (string) $data['download_info'];
            $this->download_success = (string) $data['download_success'];
             
        } else {
            $this->file_id = "";
            $this->file_name = "";
        }
        
        return;
    }
    
    function download_handler($data)
    {
        $this->setGrade(1.0);
        return ;
    }
    
    private function setFolderId($foldername = "Allgemeiner Dateiordner"){
        $cid = $this->container['cid'];
        $db = \DBManager::get();
        $stmt = $db->prepare("SELECT folder_id FROM folder WHERE name = :foldername AND seminar_id = :cid");
        $stmt->bindParam(":foldername", $foldername);
        $stmt->bindParam(":cid", $cid);
        $stmt->execute();
        $this->folder_id = $stmt->fetch()['folder_id'];
        return ;
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
            if($this->file !=  $item['name']){
                $filesarray[] = $item;
            }
        }
        return $filesarray;
    }

    private function getAttrArray() 
    {
        return array(
            'file' => $this->file, 
            'file_id' => $this->file_id, 
            'file_name' => $this->file_name, 
            'file_width' => $this->file_width,
            'file_height' => $this->file_height,
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
            'name' => $this->file_name,
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
            $this->download = $properties['file'];
        }
        if (isset($properties['file_id'])) {
            $this->download = $properties['file_id'];
        }
        if (isset($properties['file_name'])) {
            $this->download = $properties['file_name'];
        }
        
        $this->setFolderId();

        $this->save();
    }
    
    public function importContents($contents, array $files)
    {
        $file = reset($files);
        $this->file = $file->name;
        $document =  current(\StudipDocument::findBySQL('filename = ?', array($this->file)));
        $this->file_id = $document->dokument_id;

        $this->save();
    }
    
}
