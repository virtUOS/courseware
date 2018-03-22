<?php
namespace Mooc\UI\HtmlBlock;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property string $content
 */
class HtmlBlock extends Block
{
    const NAME = 'Freitext';

    public function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        $this->setGrade(1.0);

        return array('content' => formatReady($this->content));
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $content = wysiwygReady($this->content);

        return compact('content');
    }

    /**
     * Updates the block's contents.
     *
     * @param array $data The request data
     *
     * @return array The block's data
     */
    public function save_handler(array $data)
    {
        $this->authorizeUpdate();
        $content = \STUDIP\Markup::purifyHtml((string) $data['content']);
        if ($content == "") {
            $this->content = "";
        } else {
            $dom = new \DOMDocument();
            $dom->loadHTML($content);
            $xpath = new \DOMXPath($dom);
            $hrefs = $xpath->evaluate("//a");
            for ($i = 0; $i < $hrefs->length; $i++) {
                $href = $hrefs->item($i);
                if($href->getAttribute("class") == "link-extern") {
                $href->removeAttribute('target');
                $href->setAttribute("target", "_blank");
                }
            }
            $this->content = $dom->saveHTML();
        }

        return array('content' => $this->content);
    }

    /**
     * {@inheritdoc}
     */
    public function exportContents()
    {
        if (strlen($this->content) === 0) {
            return '';
        }

        $document = new \DOMDocument();
        $document->loadHTML($this->content);

        $anchorElements = $document->getElementsByTagName('a');
        foreach ($anchorElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('href')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('href'), function ($components) use ($block, $element) {
                $element->setAttribute('href', $block->buildUrl('http://internal.moocip.de', '/sendfile.php', $components));
            });
        }

        $imageElements = $document->getElementsByTagName('img');
        foreach ($imageElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('src')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('src'), function ($components) use ($block, $element) {
                $element->setAttribute('src', $block->buildUrl('http://internal.moocip.de', '/sendfile.php', $components));
            });
        }

        return $this->cw_utf8_decode(utf8_decode(\STUDIP\Markup::purifyHtml($document->saveHTML())));
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles()
    {
        /** @var \Seminar_User $user */
        global $user;

        $files = array();
        $crawler = new Crawler($this->content);
        $block = $this;

        // extract a file id from a URL
        $extractFile = function ($url) use ($user, $block) {
            return $block->applyCallbackOnInternalUrl($url, function ($components, $queryParams) use ($user) {
                if (isset($queryParams['file_id'])) {
                    $file_ref = new \FileRef($queryParams['file_id']);
                    $file = new \File($file_ref->file_id);

                    return array(
                        'id' => $queryParams['file_id'],
                        'name' => $file_ref->name,
                        'description' => $file_ref->description,
                        'filename' => $file->name,
                        'filesize' => $file->size,
                        'url' => $file->getURL(),
                        'path' => $file->getPath()
                    );
                }

                return null;
            });
        };

        // filter files referenced in anchor elements
        $crawler->filterXPath('//a')->each(function (Crawler $node) use ($extractFile, &$files) {
            $file = $extractFile($node->attr('href'));

            if ($file !== null) {
                $files[] = $file;
            }
        });

        // filter files referenced in image elements
        $crawler->filterXPath('//img')->each(function (Crawler $node) use ($extractFile, &$files) {
            $file = $extractFile($node->attr('src'));

            if ($file !== null) {
                $files[] = $file;
            }
        });

        return $files;
    }

    /**
     * {@inheritdoc}
     */
    public function importContents($contents, array $files)
    {
        $document = new \DOMDocument();
        $document->loadHTML($this->cw_utf8_decode($contents));

        $anchorElements = $document->getElementsByTagName('a');
        foreach ($anchorElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('href')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('href'), function ($components) use ($block, $element, $files) {
                parse_str($components['query'], $queryParams);
                $queryParams['file_id'] = $files[$queryParams['file_id']]->id;
                $components['query'] = http_build_query($queryParams);
                $element->setAttribute('href', $block->buildUrl($GLOBALS['ABSOLUTE_URI_STUDIP'], '/sendfile.php', $components));
            });
        }

        $imageElements = $document->getElementsByTagName('img');
        foreach ($imageElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('src')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('src'), function ($components) use ($block, $element, $files) {
                parse_str($components['query'], $queryParams);
                $queryParams['file_id'] = $files[$queryParams['file_id']]->id;
                $components['query'] = http_build_query($queryParams);
                $element->setAttribute('src', $block->buildUrl($GLOBALS['ABSOLUTE_URI_STUDIP'], '/sendfile.php', $components));
            });
        }
        $this->content = \STUDIP\Markup::purifyHtml($document->saveHTML());

        $this->save();
    }

    /**
     * Calls a callback if a given URL is an internal URL.
     *
     * @param string   $url      The url to check
     * @param callable $callback A callable to execute
     *
     * @return mixed The return value of the callback or null if the callback
     *               is not executed
     */
    public function applyCallbackOnInternalUrl($url, $callback)
    {
        if (!\Studip\MarkupPrivate\MediaProxy\isInternalLink($url) && substr($url, 0, 25) !== 'http://internal.moocip.de') {
            return null;
        }
        $components = parse_url($url);
        if (
            isset($components['path'])
            && substr($components['path'], -13) == '/sendfile.php'
            && isset($components['query'])
            && $components['query'] != ''
        ) {
            parse_str($components['query'], $queryParams);

            return $callback($components, $queryParams);
        }

        return null;
    }

    /**
     * Builds a dummy internal URL for file references.
     *
     * @param string   $baseUrl    The base URL
     * @param string   $path       The URL path
     * @param string[] $components The parts of the origin URL
     *
     * @return string The internal URL
     */
    public function buildUrl($baseUrl, $path, $components)
    {
        return rtrim($baseUrl, '/').'/'.ltrim($path, '/').'?'.$components['query'];
    }
    
    private function cw_utf8_decode($data)
    {
        if (is_array($data)) {
            $new_data = array();
            foreach ($data as $key => $value) {
                $key = studip_utf8decode($key);
                $new_data[$key] = studip_utf8decode($value);
            }
            return $new_data;
        }

        if (!preg_match('/[\200-\377]/', $data)) {
            return $data;
        } else {
            $windows1252 = array(
                "\x80" => '&#8364;',
                "\x81" => '&#65533;',
                "\x82" => '&#8218;',
                "\x83" => '&#402;',
                "\x84" => '&#8222;',
                "\x85" => '&#8230;',
                "\x86" => '&#8224;',
                "\x87" => '&#8225;',
                "\x88" => '&#710;',
                "\x89" => '&#8240;',
                "\x8A" => '&#352;',
                "\x8B" => '&#8249;',
                "\x8C" => '&#338;',
                "\x8D" => '&#65533;',
                "\x8E" => '&#381;',
                "\x8F" => '&#65533;',
                "\x90" => '&#65533;',
                "\x91" => '&#8216;',
                "\x92" => '&#8217;',
                "\x93" => '&#8220;',
                "\x94" => '&#8221;',
                "\x95" => '&#8226;',
                "\x96" => '&#8211;',
                "\x97" => '&#8212;',
                "\x98" => '&#732;',
                "\x99" => '&#8482;',
                "\x9A" => '&#353;',
                "\x9B" => '&#8250;',
                "\x9C" => '&#339;',
                "\x9D" => '&#65533;',
                "\x9E" => '&#382;',
                "\x9F" => '&#376;');
            return str_replace(
                array_values($windows1252),
                array_keys($windows1252),
                utf8_decode(mb_encode_numericentity(
                    $data,
                    array(0x100, 0xffff, 0, 0xffff),
                    'UTF-8'
                ))
            );
        }
    }
    
    private function cw_utf8_encode($data)
    {
        if (is_array($data)) {
            $new_data = array();
            foreach ($data as $key => $value) {
                $key = studip_utf8encode($key);
                $new_data[$key] = studip_utf8encode($value);
            }
            return $new_data;
        }
    
        if (!preg_match('/[\200-\377]/', $data) && !preg_match("'&#[0-9]+;'", $data)) {
            return $data;
        } else {
            return mb_decode_numericentity(
                mb_convert_encoding($data,'UTF-8', 'WINDOWS-1252'),
                array(0x100, 0xffff, 0, 0xffff),
                'UTF-8'
            );
        }
    }
}
