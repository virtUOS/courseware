<?php
namespace Mooc\UI\PortfolioBlock;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property string $content
 */
class PortfolioBlock extends Block
{
    const NAME = 'Private Notiz';
    const BLOCK_CLASS = 'portfolio';
    const DESCRIPTION = 'Diese Notiz kann niemand ausser mir selbst sehen.';

    public function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $cid = $this->container['cid'];

        require_once(get_config('PLUGINS_PATH') . '/virtUOS/EportfolioPlugin/models/EportfolioModel.class.php');
        $portfolio = \EportfolioModel::findOneBySeminar_id($cid);
        if ($portfolio){
            $owner = $portfolio->owner_id;
        }

        $this->setGrade(1.0);

        $content = $this->content;
        if (strpos($content, "<!DOCTYPE html") == 0 ) {
            $content = \STUDIP\Markup::markAsHtml($content);
        }
        $show_note = ($this->container['current_user']->id == $owner);

		$content = formatReady($content);

        return array(
            'content'   => $content,
            'show_note' => $show_note
        );
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

    protected function authorizeUpdate(){
        parent::authorizeUpdate();
        $cid = $this->container['cid'];

        require_once(get_config('PLUGINS_PATH') . '/virtUOS/EportfolioPlugin/models/EportfolioModel.class.php');
        $portfolio = \EportfolioModel::findOneBySeminar_id($cid);
        if ($portfolio){
            $owner = $portfolio->owner_id;
        }

        if ($this->container['current_user']->id != $owner) {
            throw new \Mooc\UI\Errors\AccessDenied(_cw("Sie sind nicht berechtigt diesen Block zu editieren."));
        }
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
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/portfolio/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/portfolio/portfolio-1.0.xsd';
    }

}
