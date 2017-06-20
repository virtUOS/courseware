<?
namespace Mooc\UI\GalleryBlock;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

class GalleryBlock extends Block
{
    const NAME = 'Galerie';

    function initialize()
    {
        $this->defineField('gallery_content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_folder_id', \Mooc\SCOPE_BLOCK, '');
    }

    function student_view()
    {
        $this->setGrade(1.0);
        return array_merge($this->getAttrArray(), ["showFiles" => $this->showFiles($this->gallery_folder_id ), "url"  => \URLHelper::getLink('sendfile', array()) ]);
    }

    function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getAttrArray(), ["foldernames" => $this->getFolderNames()]);
    }

    private function getAttrArray() 
    {
        return array(
            'gallery_content' => $this->gallery_content,
            'gallery_folder_id' => $this->gallery_folder_id
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset ($data['gallery_content'])) {
            $this->gallery_content = (string) $data['gallery_content'];
        } 
        if (isset ($data['gallery_folder_id'])) {
            $this->gallery_folder_id = (string) $data['gallery_folder_id'];
        } 
        return;
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
        $mimetypes = ["jpg", "png"];
        foreach ($response as $item) {
            if(in_array(substr($item["name"], -3), $mimetypes))
            {
                $item["url"] = GetDownloadLink($item['dokument_id'], $item['filename']);
                $filesarray[] = $item;
            }
        }
        
        return $filesarray;
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['gallery_content'])) {
            $this->gallery_content = $properties['gallery_content'];
        }
        if (isset($properties['gallery_folder_id'])) {
            $this->gallery_folder_id = $properties['gallery_folder_id'];
        }

        $this->save();
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/gallery/';
    }

        public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/gallery/gallery-1.0.xsd';
    }

}
