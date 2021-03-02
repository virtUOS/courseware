<?php
namespace Mooc\UI\PortfolioBlockSupervisor;

use Mooc\UI\Block;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @property string $content
 */
class PortfolioBlockSupervisor extends Block
{
    const NAME = 'Feedback';
    const BLOCK_CLASS = 'portfolio';
    const DESCRIPTION = 'Diese Notiz können nur meine Supervisor/innen lesen und beantworten';

    public function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('supervisorcontent', \Mooc\SCOPE_BLOCK, '');
    }

    public function student_view()
    {
        $cid = $this->container['cid'];

        $roles  = \DBManager::get()->query("SELECT owner_id, group_id FROM eportfolio WHERE Seminar_id = '$cid'")->fetch(\PDO::FETCH_ASSOC);

        require_once(get_config('PLUGINS_PATH') . '/virtUOS/EportfolioPlugin/models/SupervisorGroup.class.php');

        $supervisor = false;
        $group      =  \SupervisorGroup::findOneBySeminar_id($roles['group_id']);

        if ($group && $group->user) {
            $supervisoren = $group->user->pluck('user_id');

            if(in_array($this->getCurrentUser()->id, $supervisoren)) {
                $supervisor = true;
            } else {
                $supervisor = false;
            }

            if($this->getCurrentUser()->id == $roles['owner_id']) {
                $owner = true;
            } else {
                $owner = false;
            }

            $content = $this->content;
            if (strpos($content, "<!DOCTYPE html") == 0 ) {
                $content = \STUDIP\Markup::markAsHtml($content);
            }

            $content = formatReady($content);

            $this->setGrade(1.0);
        }

        if ($supervisor || $owner) {
            return array(
                'content'           => $content,
                'supervisorcontent' => $this->supervisorcontent,
                'show_note'         => true,
                'supervisor'        => $supervisor,
                'owner'             => $owner
            );
        } else {
            return array(
                'content'           => "",
                'supervisorcontent' => "",
                'show_note'         => false
            );
        }
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
                if ($href->getAttribute("class") == "link-extern") {
                    $href->removeAttribute('target');
                    $href->setAttribute("target", "_blank");
                }
            }
            $this->content = $dom->saveHTML();
            \NotificationCenter::postNotification('UserDidPostSupervisorNotiz', $this->id, \Course::findCurrent()->id);
        }

        return array(
            'content'           => formatReady($this->content),
            'supervisorcontent' => formatReady($this->supervisorcontent)
        );
    }

    /**
     * Updates the block's contents.
     *
     * @param array $data                  The request data
     *
     * @return array The block's data
     */
    public function savesupervisor_handler(array $data)
    {
        $cid = $this->container['cid'];

        $section = $this->getModel()->parent;
        $subchapter = $section->parent;
        $chapter = $subchapter->parent;

        if (\EportfolioFreigabe::hasAccess($GLOBALS['user']->id, $chapter->id)) {
            // second param in if-block is special case for uos. old studip with new wysiwyg
            if ($this->container['version']->newerThan(3.1) || $this->container['wysiwyg_refined']) {
                $this->supervisorcontent = \STUDIP\Markup::purifyHtml((string) $data['supervisorcontent']);
                \NotificationCenter::postNotification('SupervisorDidPostAnswer', $this->id, \Course::findCurrent()->id);
            } else {
              $this->supervisorcontent = (string) $data['supervisorcontent'];
              \NotificationCenter::postNotification('SupervisorDidPostAnswer', $this->id, \Course::findCurrent()->id);
            }
            return array(
                'content'           => $this->content,
                'supervisorcontent' => formatReady($this->supervisorcontent)
            );
         } else {
            throw new \AccessDeniedException(_cw("Sie sind nicht berechtigt diesen Block zu editieren."));
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/portfoliosupervisor/';
    }

    /**
     * {@inheritdoc}
     */
    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/portfoliosupervisor/portfoliosupervisor-1.0.xsd';
    }
}
