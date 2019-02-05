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
        $array['webvideo'] = json_decode($array['webvideo']);

        return $array;
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $webvideos = json_decode($this->webvideo);
        $file_ids = array();
        foreach ($webvideos as $webvideo) {
            if (!empty($webvideo->file_id)) {
                array_push($file_ids, $webvideo->file_id);
            }
        }
        $files_arr = $this->showFiles($file_ids);

        $no_files = empty($files_arr['userfilesarray']) && empty($files_arr['coursefilesarray']) && ($files_arr['video_ids_found'] == false) && empty($file_ids);

        return array_merge($this->array_rep(), array(
            'no_files' => $no_files,
            'video_files_other' => $files_arr['other_user_files'],
            'video_files_user' => $files_arr['userfilesarray'],
            'video_files_course' => $files_arr['coursefilesarray']
        ));
    }

    public function save_handler($data)
    {
        $this->authorizeUpdate();
        $this->url = (string) $data['url'];
        $this->webvideo = (string) $data['webvideo'];
        $this->webvideosettings = (string) $data['webvideosettings'];
        $this->videoTitle = \STUDIP\Markup::purifyHtml((string) $data['videoTitle']);
        $this->aspect = (string) $data['aspect'];

        return $this->array_rep();
    }

    private function showFiles($file_ids)
    {
        $coursefilesarray = array();
        $userfilesarray = array();
        $course_folders =  \Folder::findBySQL('range_id = ?', array($this->container['cid']));
        $user_folders =  \Folder::findBySQL('range_id = ? AND folder_type = ? ', array($this->container['current_user_id'], 'PublicFolder'));
        $video_id_found = false;
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

        return array('coursefilesarray' => $coursefilesarray, 'userfilesarray' => $userfilesarray, 'video_id_found' => $video_id_found, 'other_user_files' => $other_user_files);
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
                if(($source->file_name == $file->name) && ($source->file_id != '')) {
                    $source->file_id = $file->id;
                    $source->src = $file->getDownloadURL();
                }
            }
        }
        $this->webvideo = json_encode($webvideo);

        $this->save();
    }
}
