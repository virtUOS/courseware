<?php
namespace Mooc\UI\GalleryBlock;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

class GalleryBlock extends Block
{
    const NAME = 'Galerie';

    public function initialize()
    {
        $this->defineField('gallery_file_ids', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_file_names', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_folder_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_autoplay', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_autoplay_timer', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_hidenav', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_height', \Mooc\SCOPE_BLOCK, '600');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $this->setGrade(1.0);

        return array_merge(
            $this->getAttrArray(), 
            ['showFiles' => $this->showFiles($this->gallery_folder_id ), 
            'url' => \URLHelper::getLink('sendfile', array()) 
            ]
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getAttrArray(), ["foldernames" => $this->getFolderNames()]);
    }

    private function getAttrArray() 
    {
        return array(
            'gallery_file_ids'       => $this->gallery_file_ids,
            'gallery_file_names'     => $this->gallery_file_names,
            'gallery_folder_id'      => $this->gallery_folder_id,
            'gallery_autoplay'       => $this->gallery_autoplay,
            'gallery_autoplay_timer' => $this->gallery_autoplay_timer,
            'gallery_hidenav'        => $this->gallery_hidenav,
            'gallery_height'         => $this->gallery_height
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['gallery_folder_id'])) {
            $this->gallery_folder_id = (string) $data['gallery_folder_id'];
        } 
        if (isset ($data['gallery_autoplay'])) {
            $this->gallery_autoplay = (string) $data['gallery_autoplay'];
        } 
        if (isset ($data['gallery_autoplay_timer'])) {
            $this->gallery_autoplay_timer = (string) $data['gallery_autoplay_timer'];
        } 
        if (isset ($data['gallery_hidenav'])) {
            $this->gallery_hidenav = (string) $data['gallery_hidenav'];
        } 
        if (isset ($data['gallery_height']) && ($data['gallery_height'] > 0) && ($data['gallery_height'] <= 2000)) {
            $this->gallery_height = (string) $data['gallery_height'];
        } else {
            $this->gallery_height = "600";
        }
        $files = $this->showFiles($this->gallery_folder_id );
        $file_ids = array();
        $file_names = array();
        foreach ($files as $file) {
            array_push($file_ids , array($file['dokument_id']));
            array_push($file_names , array($file['filename']));
        }
        $this->gallery_file_ids = json_encode($file_ids);
        $this->gallery_file_names = json_encode($file_names);

        return;
    }

    private function getFolderNames()
    {
        $cid = $this->container['cid'];
        $db = \DBManager::get();
        $stmt = $db->prepare('
            SELECT
                *
            FROM
                folder
            WHERE
                seminar_id = :cid
        ');
        $stmt->bindParam(':cid', $cid);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function showFiles($folderId)
    {
        $db = \DBManager::get();
        $stmt = $db->prepare('
            SELECT
                *
            FROM
                dokumente
            WHERE
                range_id = :range_id
            ORDER BY
                name
        ');
        $stmt->bindParam(':range_id', $folderId);
        $stmt->execute();
        $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $filesarray = array();
        $mimetypes = ['jpg', 'png'];
        foreach ($response as $item) {
            if(in_array(substr($item['name'], -3), $mimetypes))
            {
                if (\StudipDocument::find($item['dokument_id'])->checkAccess($this->container['current_user_id'])) {
                    $item["url"] = GetDownloadLink($item['dokument_id'], $item['filename']);
                    $filesarray[] = $item;
                }
            }
        }

        return $filesarray;
    }

    public function exportProperties()
    {
       $folder_name = \DocumentFolder::find($this->gallery_folder_id)->name;

       return array_merge($this->getAttrArray() , array( 'gallery_folder_name' => $folder_name) );
    }
    
    public function getFiles()
    {
        $db = \DBManager::get();
        $stmt = $db->prepare('
            SELECT
                *
            FROM
                dokumente
            WHERE
                range_id = :range_id
        ');
        $stmt->bindParam(':range_id', $this->gallery_folder_id);
        $stmt->execute();
        $response = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $mimetypes = ['jpg', 'png'];
        $files = array();
        foreach ($response as $item) {
            if(in_array(substr($item['name'], -3), $mimetypes)) {
                array_push( $files, array (
                    'id'          => $item['dokument_id'],
                    'name'        => $item['name'],
                    'description' => $item['description'],
                    'filename'    => $item['filename'],
                    'filesize'    => $item['filesize'],
                    'url'         => $item['url'],
                    'path'        => get_upload_file_path($item['dokument_id']),
                ));
            }
        }

        return $files;
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['gallery_file_names'])) {
            $this->gallery_file_names = $properties['gallery_file_names'];
        }
        if (isset ($properties['gallery_autoplay'])) {
            $this->gallery_autoplay = $properties['gallery_autoplay'];
        } 
        if (isset ($properties['gallery_autoplay_timer'])) {
            $this->gallery_autoplay_timer = $properties['gallery_autoplay_timer'];
        } 
        if (isset ($properties['gallery_hidenav'])) {
            $this->gallery_hidenav = $properties['gallery_hidenav'];
        } 
        if (isset ($properties['gallery_height'])) {
            $this->gallery_height = $properties['gallery_height'];
        } 
        if (isset ($properties['gallery_folder_name'])) {
            $gallery_folder_name = $properties['gallery_folder_name'];
        } else {
            $gallery_folder_name = "Galerie-".$this->id;
        }
        $this->gallery_folder_id = $this->createGalleryFolder($gallery_folder_name);
        $this->moveFiles();
        $files = $this->showFiles($this->gallery_folder_id );
        $file_ids = array();
        foreach ($files as $file) {
            array_push($content , $file["dokument_id"]);
        }
        $this->gallery_file_ids = json_encode($file_ids);

        $this->save();
    }

    private function createGalleryFolder($gallery_folder_name)
    {
        $seminar_id = $this->container['cid'];
        $parent_id = md5( $seminar_id. 'top_folder');
        $description = "created by courseware";
        $permission = 7;
        global $user;

        $id = md5(uniqid('elvis',1));
        $folder_tree = \TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $seminar_id));

        $query = '
            INSERT INTO 
                folder (name, folder_id, description, range_id, seminar_id, user_id, permission, mkdate, chdate)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
        ';
        $statement = \DBManager::get()->prepare($query);
        $statement->execute(array(
            $gallery_folder_name,
            $id,
            $description,
            $parent_id,
            $seminar_id,
            $user->id,
            $permission
        ));

        if ($statement->rowCount()) {
            $folder_tree->init();
        }

        return $id;
    }

    private function moveFiles()
    {
        $seminar_id = $this->container['cid'];
        $db = \DBManager::get();
        $filenames = json_decode($this->gallery_file_names);
        foreach ($filenames as $filename) {
            $stmt = $db->prepare('
                UPDATE 
                    dokumente t1 
                INNER JOIN 
                (
                    SELECT Max(mkdate) mkdate, filename
                    FROM   dokumente 
                    GROUP BY filename 
                ) 
                AS t2 
                ON 
                    t1.filename = t2.filename
                AND 
                    t1.mkdate = t2.mkdate
                SET 
                    range_id = :gallery_folder
                WHERE 
                    t1.filename = :filename
                AND 
                    t1.seminar_id = :cid
            ');
            $stmt->bindParam(':gallery_folder', $this->gallery_folder_id);
            $stmt->bindParam(':cid', $seminar_id);
            $stmt->bindParam(':filename', $filename);
            $stmt->execute();
        }
    }

    public function importContents($contents, array $files)
    {
        $file = reset($files);
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
