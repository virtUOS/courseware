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
                $height = ($oembed->height / $oembed->width) * 860;
                $dom = new \DOMDocument;
                $dom->loadHTML($oembed->html);
                $xpath = new \DOMXPath($dom);
                $nodes = $xpath->query("//iframe");
                foreach($nodes as $node) {
                    $node->setAttribute('height', $height.'px');
                    $node->setAttribute('width', '860px');
                }
                $html = $dom->saveHTML();
                break;
            case 'photo':
                $html = '<img src="'.$oembed->url.'" width="'.$oembed->width.'" height="'.$oembed->height.'" title="'.$oembed->title.'">';
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
            'vimeo' => 'http://vimeo.com/api/oembed',
            'youtube' => 'https://www.youtube.com/oembed',
            'giphy' => 'https://giphy.com/services/oembed',
            'flickr' => 'http://www.flickr.com/services/oembed/',
            'sway' => 'https://sway.com/api/v1.0/oembed',
            'spotify' => 'https://embed.spotify.com/oembed/'
        );

        switch($embed_source) {
            case 'vimeo':
                return $endpoints[$embed_source] . '.json?url=' . rawurlencode($embed_url) . '&width=860';
            case 'youtube':
            case 'giphy':
            case 'spotify':
                return $endpoints[$embed_source] . '?url=' . rawurlencode($embed_url);
            case 'flickr':
            case 'sway':
                return $endpoints[$embed_source] . '?url=' . rawurlencode($embed_url).'&format=json';
        }

    }
    
    private function getSources() {
        $sources = array();
        $sources[] = array('name' => 'flickr', 'fullname' => 'Flickr');
        $sources[] = array('name' => 'giphy', 'fullname' => 'GIPHY');
        $sources[] = array('name' => 'sway', 'fullname' => 'Microsoft Sway');
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
