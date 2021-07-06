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
        $this->defineField('embed_time', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('embed_title', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('embed_fullwidth', \Mooc\SCOPE_BLOCK, true);
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        if(($this->embed_source == '')||($this->embed_url == '')) {
            return array('oembed' => false, 'empty' => true, 'isAuthor' => $this->getUpdateAuthorization());
        }
        $this->setGrade(1.0);
        $json_url = $this->build_request($this->embed_source, $this->embed_url);
        if(!function_exists('curl_init')) {
            return array('no_curl' => true);
        }
        $request = $this->curl_get($json_url);
        if($request == 'Unauthorized') {
            return array('oembed' => false, 'unauthorized' => true, 'isAuthor' => $this->getUpdateAuthorization());
        }
        if($request == 'Not Found') {
            return array('oembed' => false, 'not_found' => true, 'isAuthor' => $this->getUpdateAuthorization());
        }
        $oembed = json_decode($request);
        $img_class = 'cw-embedblock-image';
        if ($this->embed_fullwidth) {
            $img_class = 'cw-embedblock-image full-width';
        }

        switch ($oembed->type) {
            case 'video':
            case 'rich':
                $html = $oembed->html;
                $dom = new \DOMDocument;
                $internalErrors = libxml_use_internal_errors(true);
                $dom->loadHTML($html);
                libxml_use_internal_errors($internalErrors);
                $xpath = new \DOMXPath($dom);
                $nodes = $xpath->query("//iframe");
                foreach($nodes as $node) {
                    $src = $node->getAttribute('src');
                    $src = preg_replace("/^http:/i", "https:", $src);
                    $node->setAttribute('src', $src);
                    $node->setAttribute('class', 'cw-embedblock-iframe');
                }
                $html = $dom->saveHTML();

                if (($oembed->provider_name == 'YouTube') && ($this->embed_time != '')){
                    $time = json_decode($this->embed_time);
                    $start = $time->start;
                    $end = $time->end;
                    $dom = new \DOMDocument;
                    $dom->loadHTML($html);
                    $xpath = new \DOMXPath($dom);
                    $nodes = $xpath->query("//iframe");
                    foreach($nodes as $node) {
                        $src = $node->getAttribute('src');
                        $node->setAttribute('src', $src.'&start='.$start.'&end='.$end);
                        $node->setAttribute('class', 'cw-embedblock-iframe');
                    }
                    $html = $dom->saveHTML();
                }

                break;
            case 'photo':
                $html = '<img class="'.$img_class.'" src="'.$oembed->url.'" width="'.$oembed->width.'px"
                data-originalwidth="'.$oembed->width.'"  data-originalheight="'.$oembed->height.'" height="'.$oembed
                ->height.'px" title="'.$oembed->title.'">'; break;
            case 'link':
                if($oembed->provider_name == 'DeviantArt') {
                    $html = '<img class="'.$img_class.'" src="'.$oembed->fullsize_url.'" width="'.$oembed->width.'px"
                    data-originalwidth="'.$oembed->width.'"  data-originalheight="'.$oembed->height.'" height="'.$oembed
                    ->height.'px" title="'.$oembed->title.'">'; break;
                }
            default:
                $html = '';
        }

        return array_merge(
            $this->getAttrArray(),
            array(
                'html' => $html,
                'oembed' => $oembed,
                'embed_source' => $this->embed_source,
                'embed_title' => $this->embed_title
            )
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array_merge($this->getAttrArray(), array(
            'embed_sources' => $this->getSources(),
            'no_curl' => !function_exists('curl_init')
        ));
    }

    public function preview_view()
    {

        return array('embed_source' => $this->embed_source);
    }

    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        if (isset($data['embed_title'])) {
            $this->embed_title = \STUDIP\Markup::purifyHtml((string) $data['embed_title']);
        }
        if (isset($data['embed_url'])) {
            $this->embed_url = \STUDIP\Markup::purifyHtml((string) $data['embed_url']);
        }
        if (isset($data['embed_source'])) {
            $this->embed_source = \STUDIP\Markup::purifyHtml((string) $data['embed_source']);
        }
        if (isset($data['embed_time'])) {
            $this->embed_time =(string) $data['embed_time'];
        }
        if (isset($data['embed_fullwidth'])) {
            $this->embed_fullwidth =(string) $data['embed_fullwidth'];
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
            'vimeo' => 'https://vimeo.com/api/oembed.json',
            'youtube' => 'https://www.youtube.com/oembed',
            'giphy' => 'https://giphy.com/services/oembed',
            'flickr' => 'https://www.flickr.com/services/oembed/',
            'sway' => 'https://sway.com/api/v1.0/oembed',
            'sway.office' => 'https://sway.office.com/api/v1.0/oembed',
            'spotify' => 'https://embed.spotify.com/oembed/',
            'deviantart' => 'https://backend.deviantart.com/oembed',
            'sketchfab' => 'https://sketchfab.com/oembed',
            'codesandbox' => 'https://codesandbox.io/oembed',
            'codepen' => 'https://codepen.io/api/oembed',
            'ethfiddle' => 'https://ethfiddle.com/services/oembed/',
            'slideshare' => 'https://www.slideshare.net/api/oembed/2',
            'speakerdeck' => 'https://speakerdeck.com/oembed.json',
            'audiomack' => 'https://www.audiomack.com/oembed',
            'kidoju' => 'https://www.kidoju.com/api/oembed',
            'learningapps' => 'https://learningapps.org/oembed.php',
            'soundcloud' => 'https://soundcloud.com/oembed'
        );

        switch($embed_source) {
            case 'youtube':
            case 'giphy':
            case 'spotify':
            case 'sketchfab':
            case 'vimeo':
            case 'speakerdeck':
                return $endpoints[$embed_source] . '?url=' . rawurlencode($embed_url);
            case 'flickr':
            case 'sway':
            case 'sway.office':
            case 'codepen':
            case 'codesandbox':
            case 'ethfiddle':
            case 'slideshare':
            case 'audiomack':
            case 'kidoju':
            case 'learningapps':
                return $endpoints[$embed_source] . '?url=' . rawurlencode($embed_url).'&format=json';
            case 'deviantart':
            case 'soundcloud':
                return $endpoints[$embed_source] . '?format=json&url=' . rawurlencode($embed_url);
        }

    }

    private function getSources() {
        $sources = array();
        $sources[] = array('name' => 'audiomack', 'fullname' => 'Audiomack', 'url'=> 'https://audiomack.com/');
        $sources[] = array('name' => 'codepen', 'fullname' => 'CodePen', 'url'=> 'https://codepen.io/');
        $sources[] = array('name' => 'codesandbox', 'fullname' => 'CodeSandbox', 'url'=> 'https://codesandbox.io/');
        $sources[] = array('name' => 'deviantart', 'fullname' => 'DeviantArt', 'url'=> 'https://www.deviantart.com/');
        $sources[] = array('name' => 'ethfiddle', 'fullname' => 'EthFiddle', 'url'=> 'https://ethfiddle.com/');
        $sources[] = array('name' => 'flickr', 'fullname' => 'Flickr', 'url'=> 'https://www.flickr.com/');
        $sources[] = array('name' => 'giphy', 'fullname' => 'GIPHY', 'url'=> 'https://giphy.com/');
        $sources[] = array('name' => 'kidoju', 'fullname' => 'Kidoju', 'url'=> 'https://www.kidoju.com/');
        $sources[] = array('name' => 'learningapps', 'fullname' => 'LearningApps', 'url'=> 'https://learningapps.org/');
        $sources[] = array('name' => 'sway', 'fullname' => 'Microsoft Sway (sway.com)', 'url'=> 'https://sway.com/');
        $sources[] = array('name' => 'sway.office', 'fullname' => 'Microsoft Sway (sway.office.com)', 'url'=> 'https://sway.office.com/');
        $sources[] = array('name' => 'sketchfab', 'fullname' => 'Sketchfab', 'url'=> 'https://sketchfab.com/');
        $sources[] = array('name' => 'slideshare', 'fullname' => 'SlideShare', 'url'=> 'https://www.slideshare.net/');
        $sources[] = array('name' => 'soundcloud', 'fullname' => 'SoundCloud', 'url'=> 'https://www.soundcloud.com/');
        $sources[] = array('name' => 'speakerdeck', 'fullname' => 'Speaker Deck', 'url'=> 'https://speakerdeck.com/');
        $sources[] = array('name' => 'spotify', 'fullname' => 'Spotify', 'url'=> 'https://www.spotify.com/');
        $sources[] = array('name' => 'vimeo', 'fullname' => 'Vimeo', 'url'=> 'https://vimeo.com/');
        $sources[] = array('name' => 'youtube', 'fullname' => 'YouTube', 'url'=> 'https://www.youtube.com/');

        return $sources;
    }

    private function getAttrArray()
    {
        return array(
            'embed_title' => $this->embed_title,
            'embed_url' => $this->embed_url,
            'embed_source' => $this->embed_source,
            'embed_time' => $this->embed_time,
            'embed_fullwidth' => $this->embed_fullwidth
        );
    }

    public function exportProperties()
    {
       return $this->getAttrArray();
    }

    public function pdfexport_view()
    {
        return array();
    }

    public function getHtmlExportData()
    {
        return array_merge($this->getAttrArray(), $this->student_view());
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
        if (isset($properties['embed_title'])) {
            $this->embed_title = $properties['embed_title'];
        }
        if (isset($properties['embed_url'])) {
            $this->embed_url = $properties['embed_url'];
        }
        if (isset($properties['embed_source'])) {
            $this->embed_source = $properties['embed_source'];
        }
        if (isset($properties['embed_time'])) {
            $this->embed_time = $properties['embed_time'];
        }
        if (isset($properties['embed_fullwidth'])) {
            $this->embed_fullwidth = $properties['embed_fullwidth'];
        }

        $this->save();
    }

}
