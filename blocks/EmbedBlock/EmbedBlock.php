<?php

namespace Mooc\UI\EmbedBlock;

use Mooc\UI\Block;

/**
 * Using oEmbed to embed multimedia from other platforms
 *
 * @author <lucke@elan-ev.de>
 */

class EmbedBlock extends Block
{
    const NAME = 'Embed';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Bindet externe Inhalte wie Videos, Grafiken oder Musik ein';

    public function initialize()
    {
        $this->defineField('embed_url', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('embed_source', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }

        $embed_source = $this->embed_source;
        $embed_url = $this->embed_url;

        $json_url = $this->build_request($embed_source, $embed_url);

        $oembed = json_decode($this->curl_get($json_url));
        
        switch ($oembed->type) {
            case 'video':
            case 'rich':
                $html = $oembed->html;
                break;
            case 'photo':
                $html = '<img class="embed-block-image" src="'.$oembed->url.'" width="'.$oembed->width.'px" 
                data-originalwidth="'.$oembed->width.'"  data-originalheight="'.$oembed->height.'" height="'.$oembed
                ->height.'px" title="'.$oembed->title.'">'; break;
            default:
                $html = '';
        }

        return array_merge(
            $this->getAttrArray(),
            array('html' => $html)
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getAttrArray(), array(
            'embed_sources' => $this->getSources()
        ));
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset($data['embed_url'])) {
            $this->embed_url = (string) $data['embed_url'];
        }
        if (isset($data['embed_source'])) {
            $this->embed_source = (string) $data['embed_source'];
        }

        return;
    }

    private function curl_get($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $return = curl_exec($curl);
        curl_close($curl);

        return $return;
    }

    private function build_request($embed_source, $embed_url) {
        $endpoints = array(
            'vimeo' => 'http://vimeo.com/api/oembed.json',
            'youtube' => 'https://www.youtube.com/oembed',
            'giphy' => 'https://giphy.com/services/oembed',
            'flickr' => 'http://www.flickr.com/services/oembed/',
            'sway' => 'https://sway.com/api/v1.0/oembed',
            'spotify' => 'https://embed.spotify.com/oembed/',
            'deviantart' => 'https://backend.deviantart.com/oembed',
            'sketchfab' => 'https://sketchfab.com/oembed',
            'codesandbox' => 'https://codesandbox.io/oembed',
            'codepen' => 'https://codepen.io/api/oembed',
            'ethfiddle' => 'https://ethfiddle.com/services/oembed/',
            'amcharts' => 'https://live.amcharts.com/oembed',
            'edumedia' => 'https://www.edumedia-sciences.com/oembed.json',
            'slideshare' => 'http://www.slideshare.net/api/oembed/2',
            'speakerdeck' => 'https://speakerdeck.com/oembed.json',
            'audiomack' => 'https://www.audiomack.com/oembed',
            'kidoju' => 'https://www.kidoju.com/api/oembed',
            'learningapps' => 'http://learningapps.org/oembed.php'
            
        );

        switch($embed_source) {
            case 'youtube':
            case 'giphy':
            case 'spotify':
            case 'sketchfab':
            case 'vimeo':
            case 'edumedia':
            case 'speakerdeck':
                return $endpoints[$embed_source] . '?url=' . rawurlencode($embed_url);
            case 'flickr':
            case 'sway':
            case 'codepen':
            case 'codesandbox':
            case 'ethfiddle':
            case 'amcharts':
            case 'slideshare':
            case 'audiomack':
            case 'kidoju':
            case 'learningapps':
                return $endpoints[$embed_source] . '?url=' . rawurlencode($embed_url).'&format=json';
            case 'deviantart':
                return $endpoints[$embed_source] . '?format=json&url=' . rawurlencode($embed_url);
        }

    }

    private function getSources() {
        $sources = array();
        $sources[] = array('name' => 'amcharts', 'fullname' => 'amCharts');
        $sources[] = array('name' => 'audiomack', 'fullname' => 'Audiomack');
        $sources[] = array('name' => 'codepen', 'fullname' => 'CodePen');
        $sources[] = array('name' => 'codesandbox', 'fullname' => 'CodeSandbox');
        $sources[] = array('name' => 'deviantart', 'fullname' => 'DeviantArt');
        $sources[] = array('name' => 'edumedia', 'fullname' => 'eduMedia');
        $sources[] = array('name' => 'ethfiddle', 'fullname' => 'EthFiddle');
        $sources[] = array('name' => 'flickr', 'fullname' => 'Flickr');
        $sources[] = array('name' => 'giphy', 'fullname' => 'GIPHY');
        $sources[] = array('name' => 'kidoju', 'fullname' => 'Kidoju');
        $sources[] = array('name' => 'learningapps', 'fullname' => 'LearningApps');
        $sources[] = array('name' => 'sway', 'fullname' => 'Microsoft Sway');
        $sources[] = array('name' => 'sketchfab', 'fullname' => 'Sketchfab');
        $sources[] = array('name' => 'slideshare', 'fullname' => 'SlideShare');
        $sources[] = array('name' => 'speakerdeck', 'fullname' => 'Speaker Deck');
        $sources[] = array('name' => 'spotify', 'fullname' => 'Spotify');
        $sources[] = array('name' => 'vimeo', 'fullname' => 'Vimeo');
        $sources[] = array('name' => 'youtube', 'fullname' => 'YouTube');

        return $sources;
    }

    private function getAttrArray()
    {
        return array(
            'embed_url' => $this->embed_url,
            'embed_source' => $this->embed_source
        );
    }

    public function exportProperties()
    {
       return;
    }

    public function getFiles()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/embed/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/embed/embed-1.0.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function importProperties(array $properties)
    {
        if (isset($properties['embed_url'])) {
            $this->embed_url = $properties['embed_url'];
        }
        if (isset($properties['embed_source'])) {
            $this->embed_source = $properties['embed_source'];
        }

        $this->save();
    }

    public function importContents($contents, array $files)
    {
    }
}
