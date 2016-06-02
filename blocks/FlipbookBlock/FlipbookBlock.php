<?
namespace Mooc\UI\FlipbookBlock;

require_once 'app/models/WysiwygRequest.php';
require_once 'app/models/WysiwygDocument.php';
require_once 'lib/datei.inc.php';


use Studip\WysiwygRequest;
use Studip\WysiwygDocument;
use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;


class FlipbookBlock extends Block
{
    const NAME = 'Flipbook';
    const FOLDER_NAME = 'Allgemeiner Dateiordner';
    const FOLDER_DESCRIPTION = 'Ablage für allgemeine Ordner und Dokumente der Veranstaltung';

    function initialize()
    {
        $this->defineField('pdf', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('pdf_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('pdf_filename', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('pdf_pages', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('flipbook_rootfolder_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('flipbook_imagefolder_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('aspect', \Mooc\SCOPE_BLOCK, 'portrait');
        $this->createFlipbookFolder();
    }

    function student_view()
    {
        $this->setGrade(1.0);
        $imagefiles = $this->showFiles($this->flipbook_imagefolder_id, "jpg");
        
        // Dateien zählen
        $this->pdf_pages = count($imagefiles);
             
        return array(
            'pdf'           => $this->pdf,
            'pdf_id'        => $this->pdf_id,
            'pdf_filename'  => $this->pdf_filename,
            'pdf_pages'     => $this->pdf_pages,
            'imagefiles'    => $imagefiles,
            'aspect'        => $this->aspect
        );
        
    }


    function author_view()
    {
        $this->authorizeUpdate();
        $pdffiles = $this->showFiles($this->flipbook_rootfolder_id, "pdf");
        return array(
            'pdf'           => $this->pdf,
            'pdf_id'        => $this->pdf_id,
            'pdf_filename'  => $this->pdf_filename,
            'pdffiles'      => $pdffiles,
            'aspect'        => $this->aspect 
        );
    }
    
    
    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        
        if (isset ($data['pdf'])) {
            $this->pdf_id = (string) $data['pdf_id'];
            $this->pdf = (string) $data['pdf'];
            $this->aspect = (string) $data['aspect'];
            
            $this->pdf_filename = (new \StudipDocument($this->pdf_id))->getValue(filename);
        
            if ($this->folderNotExists("$this->pdf Imagefolder")) {
                $this->createImages();
            } else {
                $this->setImageFolder("$this->pdf Imagefolder");
            }
             
        } else {
            $this->pdf = "";
            $this->pdf_id = "";
            $this->aspect = "";
        }
        
        return;
        
    }
    
    public function upload_handler()
    {
        return WysiwygDocument::storeUploadedFilesIn($this->flipbook_rootfolder_id);
    }
    
    public function delete_handler()
    {
        // Ordner und Bilder aus Dateisystem löschen
        return ;
    }
    
    
    private function createImages()
    {
        global $TMP_PATH;
        $path = "$TMP_PATH/courseware/$this->pdf_id";
        mkdir($path, 0777, true);
        
        //Bilder erstellen
        $pdf = get_upload_file_path($this->pdf_id);
        $exportPath=$path."/%03d.jpg";
        exec("'gs' '-dNOPAUSE' '-sDEVICE=jpeg' '-r200' '-dUseCIEColor' '-dDownScaleFactor=2' '-dJPEGQ=90' '-dTextAlphaBits=4' '-dGraphicsAlphaBits=4' '-o$exportPath' '$pdf'",$output);
        
        // Anzahl der Bilder ermitteln
        $pictures = iterator_count(new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS));
        
         try {
                $flipbook_imagefolder_id = WysiwygDocument::createFolder(
                    "$this->pdf Imagefolder", "Imagefolder", $this->flipbook_rootfolder_id);
                $this->flipbook_imagefolder_id = $flipbook_imagefolder_id;
                // Bilder ins Studip-Dateisystem eintragen
                for ($i = 1; $i<= $pictures; $i++) { 
                    if ($i < 10) $file = "00$i.jpg";
                    else if ($i < 100) $file = "0$i.jpg";
                    else $file = "$i.jpg";
                    
                    $newfile = \StudipDocument::createWithFile(
                        "$path/$file",
                        array(
                        'name'          => $file,
                        'filename'      => $file,
                        'user_id'       => $GLOBALS['user']->id,
                        'author_name'   => \get_fullname(),
                        'seminar_id'    => WysiwygRequest::seminarId(),
                        'range_id'      => $flipbook_imagefolder_id,
                        'filesize'      => filesize("$path/$file")
                        )
                    );
               
                }
                
        } catch (AccessDeniedException $e) {
                $response = $e->getMessage();
        }
        
        // Temporäre Dateien entfernen
        $this->delTree($path);
        
        return;
    }

    //delete folder with all its files
    private static function delTree($dir) { 
        $files = array_diff(scandir($dir), array('.','..')); 
        foreach ($files as $file) { 
          (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
        } 
        return rmdir($dir); 
    } 
    
    private function folderNotExists($foldername) {
        $db = \DBManager::get();
        $stmt = $db->prepare("SELECT * FROM `folder` WHERE `name` = :foldername");
        $stmt->bindParam(":foldername", $foldername);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return empty($result);
        
    }
    
    private function setImageFolder($foldername){
        $db = \DBManager::get();
        $stmt = $db->prepare("SELECT `folder_id` FROM `folder` WHERE `name` = :foldername");
        $stmt->bindParam(":foldername", $foldername);
        $stmt->execute();
        $this->flipbook_imagefolder_id = $stmt->fetch()['folder_id'];
        return ;
    }
    
    private function createFlipbookFolder()
    {
        try {
            $flipbook_rootfolder_id = WysiwygDocument::createFolder(
                self::FOLDER_NAME, self::FOLDER_DESCRIPTION);
            $this->flipbook_rootfolder_id = $flipbook_rootfolder_id;
            
        } catch (AccessDeniedException $e) {
            $response = $e->getMessage();
        }
    }
    
    private function showFiles($folderId, $filetype)
    {
        $db = \DBManager::get();
        $stmt = $db->prepare("SELECT * FROM `dokumente` WHERE `range_id` = :range_id
            ORDER BY `name`");
        $stmt->bindParam(":range_id", $folderId);
        $stmt->execute();
        $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $filesarray = array();
        foreach ($response as $item) {
            if((strpos($item['filename'], $filetype) > -1) && ($this->pdf !=  $item['name'])){
                $filesarray[] = $item;
            }
        }
        return $filesarray;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/flipbook/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/flipbook/flipbook-1.0.xsd';
    }
    
    public function exportProperties()
    {
       return array(
        'pdf'             => $this->pdf, 
        'pdf_filename'    => $this->pdf_filename,
        'aspect'          => $this->aspect
        );
    }
    public function getFiles()
    {
        $document = new \StudipDocument($this->pdf_id);
        $files[] = array (
            'id' => $this->pdf_id,
            'name' => $this->pdf_filename,
            'description' => $document->description,
            'filename' => $document->filename,
            'filesize' => $document->filesize,
            'url' => $document->url,
            'path' => get_upload_file_path($this->pdf_id),
        );
        return $files;
    }
    
    
    
    public function importProperties(array $properties)
    {
        if (isset($properties['pdf'])) {
            $this->pdf = $properties['pdf'];
        }

        if (isset($properties['pdf_filename'])) {
            $this->pdf_filename = $properties['pdf_filename'];
        }
        
        if (isset($properties['aspect'])) {
            $this->pdf_filename = $properties['aspect'];
        }
        
        $this->save();
    }
    
    public function importContents($contents, array $files)
    {
        $file = reset($files);
        $this->pdf = $file->name;
        $document =  current(\StudipDocument::findBySQL('filename = ?', array($this->pdf)));
        $this->pdf_id = $document->dokument_id;
                
        // create images
        if ($this->folderNotExists("$this->pdf Imagefolder")) {
            $this->createImages();
        } else {
            $this->setImageFolder("$this->pdf Imagefolder");
        }
        
        $this->save();
    }
    

}
