<?php
namespace Mooc\UI\GalleryBlock;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

class GalleryBlock extends Block
{
    const NAME = 'Galerie';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Bilder aus einem Ordner im Dateibereich zeigen';

    public function initialize()
    {
        $this->defineField('gallery_file_ids', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_file_names', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_folder_id', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_autoplay', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_autoplay_timer', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_hidenav', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('gallery_height', \Mooc\SCOPE_BLOCK, '600');
        $this->defineField('gallery_show_names', \Mooc\SCOPE_BLOCK, '0');
    }   

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }

        $this->setGrade(1.0);
        $files = $this->showFiles($this->gallery_folder_id);
        $gallery_has_files = sizeOf($files) > 0;
        $current_folder = \Folder::find($this->gallery_folder_id);
        $folder_is_public = in_array($current_folder->folder_type, array('StandardFolder','CoursePublicFolder', 'MaterialFolder', 'PublicFolder'), true);

        if ($current_folder->folder_type == 'HiddenFolder') {
            if ($current_folder->data_content['download_allowed'] == 1) {
                $folder_is_public = true;
            }
        }

        return array_merge(
            $this->getAttrArray(), 
            array(
                'showFiles' => $files,
                'userIsAuthorized' => $this->getUpdateAuthorization(),
                'galleryHasFiles' => $gallery_has_files,
                'noFolder' => $this->gallery_folder_id == '',
                'folderIsPublic' => $folder_is_public
            )
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $course_folders = \Folder::findBySQL('range_id = ? AND folder_type IN (?)', array($this->container['cid'], array('StandardFolder','CoursePublicFolder', 'MaterialFolder')));
        $hidden_folders = $this->getHiddenFolders();
        $course_folders = array_merge($course_folders, $hidden_folders);
        $user_folders = \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));

        $folders = $this->buildFoldersArray($course_folders);
        $user_folders = $this->buildFoldersArray($user_folders);

        return array_merge($this->getAttrArray(), array("folders" => $folders, "user_folders" => $user_folders));
    }

    private function getHiddenFolders()
    {
        $folders = array();

        $hidden_folders = \Folder::findBySQL('range_id = ? AND folder_type = ?', array($this->container['cid'], 'HiddenFolder'));

        foreach ($hidden_folders as $hidden_folder) {
            if($hidden_folder->data_content['download_allowed'] == 1) {
                array_push($folders, $hidden_folder);
            }
        }

        return $folders;
    }

    public function preview_view()
    {
        return array('img' => $this->showFiles($this->gallery_folder_id)[0]['url']);
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
            'gallery_height'         => $this->gallery_height,
            'gallery_show_names'     => $this->gallery_show_names
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
        if (isset ($data['gallery_show_names'])) {
            $this->gallery_show_names = (string) $data['gallery_show_names'];
        } 
        if (isset ($data['gallery_height']) && ($data['gallery_height'] > 0) && ($data['gallery_height'] <= 2000)) {
            $this->gallery_height = (string) $data['gallery_height'];
        } else {
            $this->gallery_height = "600";
        }
        $this->setGalleryFiles();

        return;
    }

    private function buildFoldersArray($folders)
    {
        $folders_array = [];
        if ($folders) {
            foreach($folders as $folder){
                $folder = $folder->getTypedFolder();
                $response = \FileRef::findBySQL('folder_id = ?', array($folder->id));
                $counter = 0;
                foreach ($response as $item) {
                    if (!$item->terms_of_use->fileIsDownloadable($item, false)) {
                        continue;
                    }
                    if ($item->isImage()) {
                        $counter++;
                    }
                }
                $folders_array[] = array(
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'folder_type' => $folder->folder_type,
                    'size' => $counter,
                    'has_images' => $counter > 0
                );
            }
        }

        return $folders_array;
    }

    private function showFiles($folder_id)
    {
        $current_folder = \Folder::find($folder_id);
        $filesarray = array();
        if (!in_array($current_folder->folder_type, array('StandardFolder','CoursePublicFolder', 'MaterialFolder', 'HiddenFolder', 'PublicFolder'), true)) {
            return $filesarray;
        }

        if ($current_folder->folder_type == 'HiddenFolder'){
            if($current_folder->data_content['download_allowed'] != 1) {
                return $filesarray;
            }
        }

        $response = \FileRef::findBySQL('folder_id = ?', array($folder_id));
        foreach ($response as $item) {
            if (!$item->terms_of_use->fileIsDownloadable($item, false)) {
                continue;
            }
            if ($item->isImage() && $item->mime_type != 'image/svg+xml') {
                $filesarray[] = array(
                    "id"    => $item->id,
                    "name"  => $item->name,
                    "url"   => $item->getDownloadURL()
                );
            }
        }
        usort($filesarray, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $filesarray;
    }


    public function exportProperties()
    {
        $folder_name = \Folder::find($this->gallery_folder_id)->name;
        $this->setGalleryFiles();

        return array_merge($this->getAttrArray() , array( 'gallery_folder_name' => $folder_name) );
    }

    public function getHtmlExportData()
    {
        //todo map files
        $gallery_file_paths = [];
        $file_ids = json_decode($this->gallery_file_ids);

        foreach ($file_ids as $file_id) {
            $file_ref = new \FileRef($file_id);
            $file = new \File($file_ref->file_id);
            array_push($gallery_file_paths, './' . $file_id[0] . '/' . $file->name);
        }

        return array_merge($this->getAttrArray(), array('gallery_file_paths' => $gallery_file_paths));
    }

    public function getFiles()
    {
        $file_ids = json_decode($this->gallery_file_ids);

        $files = array();
        if ($file_ids) {
            foreach ($file_ids as $file_id) {
                if ($file_id == '') {
                    continue;
                }
                $file_ref = new \FileRef($file_id);
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
        if (isset ($properties['gallery_show_names'])) {
            $this->gallery_show_names = $properties['gallery_show_names'];
        }
        if (isset ($properties['gallery_folder_name'])) {
            $gallery_folder_name = $properties['gallery_folder_name'];
        } else {
            $gallery_folder_name = "Galerie-".$this->id;
        }
        $this->gallery_folder_id = $this->createGalleryFolder($gallery_folder_name);

        $this->save();
    }

    private function createGalleryFolder($gallery_folder_name)
    {
        global $user;
        $cid = $this->container['cid'];
        $courseware_folder = \Folder::findOneBySQL('range_id = ? AND name LIKE ? ORDER BY mkdate DESC', array( $cid , '%Courseware-Import%'));
        $parent_folder = \FileManager::getTypedFolder($courseware_folder->id);
        $request = array('name' => $gallery_folder_name, 'description' => 'gallery folder');
        $new_folder = new \StandardFolder();
        $new_folder->setDataFromEditTemplate($request);
        $new_folder->user_id = $user->id;
        $folder = $parent_folder->createSubfolder($new_folder);

        return $folder->id;
    }

    private function moveFiles()
    {
        $current_user = \User::findCurrent();
        $file_ids = json_decode($this->gallery_file_ids);
        $gallery_folder = \FileManager::getTypedFolder($this->gallery_folder_id);
        foreach ($file_ids as $file_id) {
            $file_ref = new \FileRef($file_id);
            \FileManager::moveFileRef($file_ref, $gallery_folder, $current_user);
        }
    }

    private function setGalleryFiles()
    {
        $files = $this->showFiles($this->gallery_folder_id);
        $file_ids = array();
        $file_names = array();
        foreach ($files as $file) {
            array_push($file_ids , array($file['id']));
            array_push($file_names , array($file['name']));
        }
        $this->gallery_file_ids = json_encode($file_ids);
        $this->gallery_file_names = json_encode($file_names);
    }

    public function importContents($contents, array $files)
    {
        $file_ids = array();
        $file_names = array_map('current', json_decode($this->gallery_file_names));
        $used_files = array();

        foreach($files as $file){
            if ($file->name == '') {
                continue;
            }
            if(in_array($file->name, $file_names)) {
                array_push($file_ids , array($file->id));
                array_push($used_files , $file->id);
            }
        }
        $this->gallery_file_ids = json_encode($file_ids);
        $this->moveFiles();

        $this->save();
        return $used_files;
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
