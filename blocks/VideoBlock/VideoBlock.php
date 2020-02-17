<?php

namespace Mooc\UI\VideoBlock;

use Mooc\UI\Block;

/**
 * @property string $url
 */
class VideoBlock extends Block
{
    const NAME = 'Video';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Spielt ein Video aus dem Dateibereich oder von einer URL ab';

    public function initialize()
    {
        $this->defineField('url', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('webvideo', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('webvideosettings', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('videoTitle', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('aspect', \Mooc\SCOPE_BLOCK, 'aspect-169');
    }

    private function array_rep() {
        return array(
            'url'               => $this->url,
            'webvideo'          => $this->webvideo,
            'webvideosettings'  => $this->webvideosettings,
            'videoTitle'        => $this->videoTitle,
            'aspect'            => $this->aspect
        );
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $this->setGrade(1.0);
        $array = $this->array_rep();

        if ($array['webvideo'] != '') {
            $array['webvideo'] = json_decode($array['webvideo'], true);
            if (is_array($array['webvideo']) > 0) {
                foreach($array['webvideo'] as &$webvideo) {
                    if($webvideo['source'] == 'file') {
                        $file = \FileRef::find($webvideo['file_id']);
                        if($file) {
                            $webvideo['src'] = ($file->terms_of_use->fileIsDownloadable($file, false)) ? $file->getDownloadURL() : '';
                        } else {
                            $webvideo['src'] = '';
                        }
                    }
                }
            }

        }
        $array['isAuthor'] = $this->getUpdateAuthorization();

        return $array;
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $webvideos = json_decode($this->webvideo);
        $file_ids = array();
        foreach ((array)$webvideos as $webvideo) {
            if (!empty($webvideo->file_id)) {
                array_push($file_ids, $webvideo->file_id);
            }
        }
        $files_arr = $this->showFiles($file_ids);

        $no_files = empty($files_arr['userfilesarray']) && empty($files_arr['coursefilesarray']) && empty($files_arr['other_user_files']) && empty($file_ids);

        return array_merge($this->array_rep(), array(
            'no_files' => $no_files,
            'video_files_other' => $files_arr['other_user_files'],
            'video_files_user' => $files_arr['userfilesarray'],
            'video_files_course' => $files_arr['coursefilesarray']
        ));
    }

    public function preview_view()
    {
        return $this->student_view();
    }

    public function save_handler($data)
    {
        $this->authorizeUpdate();
        $this->url = (string) $data['url'];
        $this->webvideo = (string) $data['webvideo'];
        $this->webvideosettings = (string) $data['webvideosettings'];
        $this->videoTitle = \STUDIP\Markup::purifyHtml((string) $data['videoTitle']);
        $this->aspect = (string) $data['aspect'];

        if($data['recording'] != '') {
            $this->webvideosettings = 'controls';
            $this->store_recording($data['recording']);
        }

        return $this->array_rep();
    }

    private function store_recording($video) 
    {
        global $user;

        $video = explode(',', $video)[1];
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);
        //create file in temp dir
        if ($this->videoTitle == '') {
            $filename = 'Courseware-Aufnahme-'.date("d.m.Y-H:i", time()).'.webm';
        } else {
            $filename = trim($this->videoTitle).'-'.date("d.m.Y-H:i", time()).'.webm';
        }
        file_put_contents($tempDir.'/'.$filename, base64_decode($video));
        // get personal root folder
        $root_folder = \Folder::findTopFolder($GLOBALS['SessionSeminar']);
        $parent_folder = \FileManager::getTypedFolder($root_folder->id);
        $subfolders = $parent_folder->getSubfolders();
        $cw_folder = null;
        // search courseware upload folder
        foreach($subfolders as $subfolder) {
            if ($subfolder->name == 'Courseware-Upload') {
                $cw_folder = $subfolder;
            }
        }
        // create courseware upload folder
        if ($cw_folder == null) {
            $request = array('name' => 'Courseware-Upload', 'description' => 'folder for courseware content');
            $new_folder = new \CoursePublicFolder();
            $new_folder->setDataFromEditTemplate($request);
            $new_folder->user_id = $user->id;
            $cw_folder = $parent_folder->createSubfolder($new_folder);
        }
        $folder = \FileManager::getTypedFolder($cw_folder->id);
        // create studip file
        $video_file = [
                'name'     => $filename,
                'type'     => 'video/webm',
                'tmp_name' => $tempDir.'/'.$filename,
                'size'     => filesize($tempDir.'/'.$filename),
                'user_id'  => $user->id
            ];
        
        $new_reference = $folder->createFile($video_file);

        $webvideo = [
            'src'       => $new_reference->download_url,
            'source'    => 'file',
            'type'      => 'webm',
            'query'     => 'normal',
            'media'     => '',
            'attr'      => '',
            'file_id'   => $new_reference->id,
            'file_name' => $new_reference->name
        ];
        $this->webvideo = json_encode(array($webvideo));

        $this->deleteRecursively($tempDir);
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
                if (($ref->isVideo()) && (!$ref->isLink())) {
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
                if (($ref->isVideo()) && (!$ref->isLink())) {
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

        return array('coursefilesarray' => $coursefilesarray, 'userfilesarray' => $userfilesarray, 'other_user_files' => $other_user_files);
    }

    /**
     * {@inheritdoc}
     */
    public function exportProperties()
    {
        return $this->array_rep();
    }

    public function getFiles()
    {
        if ($this->webvideo == '') {
            return array();
        }
        $sources = json_decode($this->webvideo);
        foreach ($sources as $source) {
            if ($source->file_id != '') {
                $file_ref = new \FileRef($source->file_id);
                $file = new \File($file_ref->file_id);
            } else {
                // for old data structure without file_id element
                parse_str($source->src, $queryParams);
                $file_ref = new \FileRef($queryParams['file_id']);
                $file = new \File($file_ref->file_id);
            }
            if ($file_ref && $file) {
                $files[] = array(
                    'id' => $file_ref->id,
                    'name' => $file_ref->name,
                    'description' => $file_ref->description,
                    'filename' => $file->name,
                    'filesize' => $file->size,
                    'url' => $file->getURL(),
                    'path' => $file->getPath()
                );
            }
        }
    if (empty($files)) {
        $files = array();
    }

        return $files;
    }
    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/video/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/video/video-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['url'])) {
            $this->url = $properties['url'];
        }

        if (isset($properties['webvideo'])) {
            $this->webvideo = $properties['webvideo'];
        }

        if (isset($properties['webvideosettings'])) {
            $this->webvideosettings = $properties['webvideosettings'];
        }

        if (isset($properties['aspect'])) {
            $this->aspect = $properties['aspect'];
        }

        if (isset($properties['videoTitle'])) {
            $this->videoTitle = $properties['videoTitle'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
        $webvideo = json_decode($this->webvideo);
        foreach($files as $file){
            if ($file->name == '') {
                continue;
            }
            foreach ($webvideo as &$source) {
                $source_filename = $this->cleanFileName($source->file_name);
                $file_name = $this->cleanFileName($file->name);
                if (empty($source_filename)) {
                    parse_str($source->src, $queryParams);
                    $source_filename = $this->cleanFileName($queryParams['file_name']);
                }

                if($source_filename == $file_name) {
                    $source->file_id = $file->id;
                    $source->src = $file->getDownloadURL();
                    $source->file_name = $file->name;
                    $this->webvideo = json_encode($webvideo);

                    $this->save();
                    return array($file->id);
                }
            }
        }

    }

    private function cleanFileName($filename)
    {
        $filename = str_replace(' ', '', $filename);
        $filename = str_replace('_', '', $filename);
        $filename = str_replace('+', '', $filename);
        $filename = str_replace('-', '', $filename);

        return $filename;
    }
}
