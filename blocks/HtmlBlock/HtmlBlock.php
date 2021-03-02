<?php
namespace Mooc\UI\HtmlBlock;

use Mooc\UI\Block;

/**
 * @property string $content
 */
class HtmlBlock extends Block
{
    const NAME = 'Freitext';
    const BLOCK_CLASS = 'multimedia';
    const DESCRIPTION = 'Erstellen von Inhalten mit dem WYSIWYG-Editor';

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

        $content = $this->content;
        if (strpos($content, "<!DOCTYPE html") == 0 ) {
            $content = \STUDIP\Markup::markAsHtml($content);
        }

        $content = formatReady($content);

        $encoding = '&lt;?xml encoding="utf-8" ?&gt;';

        if (strrpos($content, $encoding) !== false) {
            $content = \str_replace($content, $encoding);
        }

        return array('content' => $content);
    }

    public function author_view()
    {
        $this->authorizeUpdate();
        $content = \STUDIP\Markup::markAsHtml($this->content);

        return compact('content');
    }

    public function preview_view()
    {
        $content = $this->content;
        if (strpos($content, "<!DOCTYPE html") == 0 ) {
            $content = \STUDIP\Markup::markAsHtml($content);
        }
        $content = strip_tags(formatReady($content));

        $encoding = '&lt;?xml encoding="utf-8" ?&gt;';

        if (strrpos($content, $encoding) !== false) {
            $content = \str_replace($content, $encoding);
        }
        
        if (strlen($content) > 240){
            $content = substr($content, 0, 240).'…';
        }
        return array('content' => $content);
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
            $dom->loadHTML('<?xml encoding="utf-8" ?>'.$content);
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
        $encoding = '<?xml encoding="utf-8" ?>';
        $pos = strrpos($this->content, $encoding);
        if ($pos === false) {
            $content = $encoding.$this->content;
        } else {
            $content = $this->content;
        }
        $document->loadHTML($content);

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

        return $document->saveHTML();
    }

    /**
     * {@inheritdoc}
     */
    public function getFiles()
    {
        /** @var \Seminar_User $user */
        global $user;

        $files = array();
        $block = $this;
        $document = new \DOMDocument();
        $encoding = '<?xml encoding="utf-8" ?>';
        $pos = strrpos($this->content, $encoding);
        if ($pos === false) {
            $content = $encoding.$this->content;
        } else {
            $content = $this->content;
        }
        $document->loadHTML($content);

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
                        'url' => $this->isFileAnURL($file_ref),
                        'path' => $file->getPath()
                    );
                }

                return array();
            });
        };

        $anchorElements = $document->getElementsByTagName('a');
        foreach ($anchorElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('href')) {
                continue;
            }
            $file = $extractFile($element->getAttribute('href'));
            if ($file !== null) {
                $files[] = $file;
            }
        }

        $imageElements = $document->getElementsByTagName('img');
        foreach ($imageElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('src')) {
                continue;
            }
            $file = $extractFile($element->getAttribute('src'));
            if ($file !== null) {
                $files[] = $file;
            }
        }

        return $files;
    }

    public function getHtmlExportData()
    {
        if (strlen($this->content) === 0) {
            return '';
        }

        $document = new \DOMDocument();
        $encoding = '<?xml encoding="utf-8" ?>';
        $pos = strrpos($this->content, $encoding);
        if ($pos === false) {
            $content = $encoding.$this->content;
        } else {
            $content = $this->content;
        }
        $document->loadHTML($content);

        $anchorElements = $document->getElementsByTagName('a');
        foreach ($anchorElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('href')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('href'), function ($components) use ($block, $element) {
                $url_comp = parse_url($element->getAttribute('src'));
                $query = parse_str($url_comp['query'], $params);
                $element->setAttribute('href', './'. $params['file_id'] .'/'. $params['file_name']);
            });
        }

        $imageElements = $document->getElementsByTagName('img');
        foreach ($imageElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('src')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('src'), function ($components) use ($block, $element) {
                $url_comp = parse_url($element->getAttribute('src'));
                $query = parse_str($url_comp['query'], $params);
                $element->setAttribute('src', './'. $params['file_id'] .'/'. $params['file_name']);
            });
        }

        return $document->saveHTML();
    }

    /**
     * {@inheritdoc}
     */
    public function importContents($contents, array $files)
    {
        $used_files = array();
        $document = new \DOMDocument();
        $encoding = '<?xml encoding="utf-8" ?>';
        $pos = strrpos($contents, $encoding);
        if ($pos === false) {
            $content = $encoding.$contents;
        } else {
            $content = $contents;
        }
        $document->loadHTML($content);
        $anchorElements = $document->getElementsByTagName('a');
        foreach ($anchorElements as $element) {
            if (!$element instanceof \DOMElement || !$element->hasAttribute('href')) {
                continue;
            }
            $block = $this;
            $this->applyCallbackOnInternalUrl($element->getAttribute('href'), function ($components) use ($block, $element, $files, &$used_files) {
                parse_str($components['query'], $queryParams);
                $queryParams['file_id'] = $files[$queryParams['file_id']]->id;
                if($queryParams['file_id'] == null) {
                    foreach ($files as $file_ref) {
                        if($file_ref->name == $queryParams['file_name']) {
                            $queryParams['file_id'] = $file_ref->id;
                            break;
                        }
                    }
                }
                array_push($used_files, $queryParams['file_id']);
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
            $this->applyCallbackOnInternalUrl($element->getAttribute('src'), function ($components) use ($block, $element, $files, &$used_files) {
                parse_str($components['query'], $queryParams);
                $queryParams['file_id'] = $files[$queryParams['file_id']]->id;
                if($queryParams['file_id'] == null) {
                    foreach ($files as $file_ref) {
                        if($file_ref->name == $queryParams['file_name']) {
                            $queryParams['file_id'] = $file_ref->id;
                            break;
                        }
                    }
                }
                array_push($used_files, $queryParams['file_id']);
                $components['query'] = http_build_query($queryParams);
                $element->setAttribute('src', $block->buildUrl($GLOBALS['ABSOLUTE_URI_STUDIP'], '/sendfile.php', $components));
            });
        }
        $this->content = \STUDIP\Markup::purifyHtml($document->saveHTML());

        $this->save();
        return $used_files;
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

}
