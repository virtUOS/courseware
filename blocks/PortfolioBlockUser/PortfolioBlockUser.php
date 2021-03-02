<?
namespace Mooc\UI\PortfolioBlockUser;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property string $content
 */
class PortfolioBlockUser extends Block
{
    const NAME = 'Öffentliche Notiz';
    const BLOCK_CLASS = 'portfolio';
    const DESCRIPTION = 'Diese Notiz können alle Nutzer lesen, die Zugriff auf mein Portfolio haben';

    public function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $cid = $this->container['cid'];

        require_once(get_config('PLUGINS_PATH') . '/virtUOS/EportfolioPlugin/models/EportfolioModel.class.php');

        $this->setGrade(1.0);

        $content = $this->content;
        if (strpos($content, "<!DOCTYPE html") == 0 ) {
            $content = \STUDIP\Markup::markAsHtml($content);
        }
		$content = formatReady($content);

        return array(
            'content' => $content,
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
            \NotificationCenter::postNotification('UserDidPostNotiz', $this->id, \Course::findCurrent()->id);
            $this->content = $dom->saveHTML();
        }

        return array('content' => $this->content);
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/portfoliouser/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/portfoliouser/portfoliouser-1.0.xsd';
    }
}
