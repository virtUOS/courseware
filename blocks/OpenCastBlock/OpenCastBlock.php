<?php
namespace Mooc\UI\OpenCastBlock;

use Mooc\UI\Block;
use Opencast\LTI\OpencastLTI;
use Opencast\LTI\LTIResourceLink;
use Opencast\Models\OCConfig;

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
        $plugin_manager = \PluginManager::getInstance();
        $oc_present = false;
        if (!$plugin_manager->getPlugin('OpenCast')) {
            return array('oc_present' => $oc_present);
        } else {
            $plugin_info = $plugin_manager->getPluginInfo('OpenCast');
            if (!$plugin_manager->isPluginActivated($plugin_info['id'], $this->getModel()->seminar_id)) {
                return array('oc_present' => $oc_present);
            } else {
                $oc_present = true;
            }
        }

        $this->setGrade(1.0);
        $opencast_content_json = json_decode($this->opencast_content);
        $url_mp4 = $opencast_content_json->url_mp4;
        $useplayer = $opencast_content_json->useplayer;

        $url_opencast = ($useplayer == 'paella')
            ? $opencast_content_json->url_opencast_paella
            : $opencast_content_json->url_opencast_theodul;

        $course_id = $this->container['cid'];

        $config = OCConfig::getConfigForCourse($course_id);

        $lti_launch_data = OpencastLTI::generate_lti_launch_data(
            $GLOBALS['user']->id,
            $course_id,
            LTIResourceLink::generate_link('series','view complete series for course')
            //OpencastLTI::generate_tool('series', $this->connectedSeries[0]['series_id'])
        );

        $lti_data = OpencastLTI::sign_lti_data(
            $lti_launch_data,
            $config['lti_consumerkey'],
            $config['lti_consumersecret'],
            OpencastLTI::getSearchUrl($course_id)
        );

        return array_merge($this->getAttrArray(),
            array(
                'oc_present'   => $oc_present,
                'url_mp4'      => $url_mp4,
                'url_opencast' => $url_opencast,
                'useplayer'    => $useplayer,
                'url_isset'    =>  ($url_opencast != ''),
                'lti_data'     => json_encode($lti_data),
                'search_url'   => OpencastLTI::getSearchUrl($course_id)
            )
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $opencast_installed = false;
        $opencast_active = false;
        $plugin_manager = \PluginManager::getInstance();
        if (!$plugin_manager->getPlugin('OpenCast')) {
            return array('opencast_installed' => $opencast_installed);
        } else {
            $opencast_installed = true;
            $plugin_info = $plugin_manager->getPluginInfo('OpenCast');
            if (!$plugin_manager->isPluginActivated($plugin_info['id'], $this->getModel()->seminar_id)) {
                return array('opencast_installed' => $opencast_installed, 'opencast_active' => $opencast_active);
            } else {
                $opencast_active = true;
            }
        }
        $url = $plugin_manager->getPlugin('OpenCast')->getPluginURL();
        $course_id = $this->container['cid'];
        $ocmodel = new \OCCourseModel($course_id);
        $oc_episodes = $ocmodel->getEpisodesforREST();

        $search_client = \SearchClient::create($course_id);
        $video_url_theodul = $search_client->getBaseURL() . "/engage/theodul/ui/core.html?mode=embed&id=";
        $video_url_paella = $search_client->getBaseURL() . "/paella/ui/embed.html?id=";

        $episodes = [];
        foreach($oc_episodes as $episode){
            $presenter_download = $episode['presenter_download'];
            $first_key = key($presenter_download);
            $url_mp4 = $episode['presenter_download'][$first_key]['url'];
            $url_opencast_theodul = \URLHelper::getURL($video_url_theodul . $episode['id']);
            $url_opencast_paella = \URLHelper::getURL($video_url_paella . $episode['id']);
            array_push($episodes, array(
                'url_mp4' => $url_mp4,
                'url_opencast_theodul' => $url_opencast_theodul,
                'url_opencast_paella' => $url_opencast_paella,
                'title' => $episode['title'],
                'id' => $episode['id']
                ));
        }

        if ($this->opencast_content != '') {
            $opencast_content_json = json_decode($this->opencast_content);
            $opencastid = $opencast_content_json->id;
            $useplayer = $opencast_content_json->useplayer;
        } else {
            $useplayer = 'theodul';
        }

        return array_merge($this->getAttrArray(), array(
            'opencast_installed' => $opencast_installed,
            'opencast_active' => $opencast_active,
            'episodes' => $episodes,
            'opencastid' => $opencastid,
            'useplayer' => $useplayer,
            'opencast' => true
        ));
    }

    public function preview_view()
    {

        return;
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
