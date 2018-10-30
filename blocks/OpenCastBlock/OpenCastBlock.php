<?php
namespace Mooc\UI\OpenCastBlock;

use Mooc\UI\Block;

class OpenCastBlock extends Block 
{
    const NAME = 'Opencast';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Bindet Opencast Videos mit Hilfe des OpencastPlugins ein';
    const HINT = 'Für diesen Block muss das Opencast Plugin aktiviert sein';

    public function initialize()
    {
        $this->defineField('opencast_content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $this->setGrade(1.0);
        $opencast_content_json = json_decode($this->opencast_content);
        $url_mp4 = $opencast_content_json->url_mp4;
        $url_opencast = $opencast_content_json->url_opencast;
        $useplayer = $opencast_content_json->useplayer;

        return array_merge($this->getAttrArray(), 
            array(
                'url_mp4' => $url_mp4,
                'url_opencast' => $url_opencast,
                'useplayer' => $useplayer,
                'url_isset' => ($url_mp4 != '') && ($url_opencast != '')
            )
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $plugin_manager = \PluginManager::getInstance();
        if ($plugin_manager->getPlugin('OpenCast') == NULL) {
            return array('opencast' => false);
        }
        $url = $plugin_manager->getPlugin('OpenCast')->getPluginURL();
        $course_id = $this->container['cid'];
        $ocmodel = new \OCCourseModel($course_id);
        $oc_episodes = $ocmodel->getEpisodesforREST();

        $search_client = \SearchClient::getInstance($course_id);
        $video_url = $search_client->getBaseURL() . "/engage/theodul/ui/core.html?id=";

        $episodes = [];
        foreach($oc_episodes as $episode){
            $presenter_download = $episode['presenter_download'];
            $first_key = key($presenter_download);
            $url_mp4 = $episode['presenter_download'][$first_key]['url'];
            $url_opencast = \URLHelper::getURL($video_url . $episode['id']);
            array_push($episodes, array(
                'url_mp4' => $url_mp4, 
                'url_opencast' => $url_opencast, 
                'title' => $episode['title'],
                'id' => $episode['id']
                ));
        }

        if ($this->opencast_content != '') {
            $opencast_content_json = json_decode($this->opencast_content);
            $opencastid = $opencast_content_json->id;
            $useplayer = $opencast_content_json->useplayer;
        }

        return array_merge($this->getAttrArray(), array(
            'episodes' => $episodes,
            'opencastid' => $opencastid,
            'useplayer' => $useplayer,
            'opencast' => true
        ));
    }

    private function getAttrArray() 
    {
        return array(
            'opencast_content' => $this->opencast_content
        );
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (isset ($data['opencast_content'])) {
            $this->opencast_content = (string) $data['opencast_content'];
        } 

        return;
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/opencast/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/opencast/opencast-1.0.xsd';
    }

    public function importProperties(array $properties)
    {
        if (isset($properties['opencast_content'])) {
            $this->opencast_content = $properties['opencast_content'];
        }

        $this->save();
    }
}
