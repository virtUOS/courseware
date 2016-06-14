<?
namespace Mooc\UI\WallNewspaperSidebarBlock;

use Mooc\UI\Block;

/**
 * @property string $content
 */
class WallNewspaperSidebarBlock extends Block
{
    const NAME = 'Wandzeitung in der Sidebar';

    function initialize()
    {
        $this->defineField('content', \Mooc\SCOPE_BLOCK, '');
        $this->defineField('wall_newspaper_block_id', \Mooc\SCOPE_BLOCK, null);
    }

    function student_view()
    {
        $this->setGrade(1.0);

        $visible = $this->shouldHtmlBlockBeVisible();
        $content = formatReady($this->content);
        $wall_newspaper_block_id = $this->wall_newspaper_block_id;
        return compact('visible', 'content', 'wall_newspaper_block_id');
    }


    function author_view()
    {
        $this->authorizeUpdate();

        // content
        if ($this->container['wysiwyg_refined']) {
            $content = wysiwygReady($this->content);
        } else {
            $content = htmlReady($this->content);
        }

        $wnblocks = $this->findAllWallNewspaperBlocks();

        return compact('content', 'wnblocks');
    }

    /**
     * Updates the block's contents.
     *
     * @param array $data   The request data
     *
     * @return array The block's data
     */
    public function save_handler(array $data)
    {
        $this->authorizeUpdate();

        if (!isset($data['content'])) {
            throw new \Mooc\UI\Errors\BadRequest('Parameter "content" is required.');
        }

        if (!isset($data['wn_id'])) {
            throw new \Mooc\UI\Errors\BadRequest('Parameter "wn_id" is required.');
        }

        // second param in if-block is special case for uos. old studip with new wysiwyg
        if ($this->container['version']->newerThan(3.1) || $this->container['wysiwyg_refined']) {
            $this->content = \STUDIP\Markup::markAsHtml(\STUDIP\Markup::purify((string) $data['content']));
        } else {
            $this->content = (string) $data['content'];
        }

        // save the wn_id (wall_newspaper_block_id)
        $this->wall_newspaper_block_id = (int) $data['wn_id'];


        return array('content' => $this->content, 'wn_id' => $this->wall_newspaper_block_id);
    }


    private function shouldHtmlBlockBeVisible()
    {
        // users who may edit this block should see it always
        if ($this->container['current_user']->canUpdate($this)) {
            return true;
        }

        // find the progress of the associated WallNewspaperBlock and
        // return true if the user solved that block
        $progress = \Mooc\DB\UserProgress::find(array((int) $this->wall_newspaper_block_id, $this->container['current_user_id']));
        return $progress && $progress->getPercentage() === 1;
    }

    private function findAllWallNewspaperBlocks()
    {
        $wnblocks = array();

        foreach (\Mooc\DB\Block::findBySQL('seminar_id = ? AND type = ?', array($this->container['cid'], 'WallNewspaperBlock')) as $block) {
            $ancestors = $block->getAncestors();
            $section = end($ancestors);

            // do not show courseware
            array_shift($ancestors);

            $order = array_merge(
                array_map(function ($b) { return (int) $b->position; }, $ancestors),
                array((int) $block->position));

            $wnblocks[] = array(
                'id' => $block->id,
                'breadcrumbs' => join(" » ", array_map(function ($b) { return htmlReady($b->title); }, $ancestors)),
                'selected' => (int) $block->id === (int) $this->wall_newspaper_block_id,
                'order' => $order
            );
        }

        $recursive_cmp = function ($ary1, $ary2)
        {
            for ($i = 0, $len = min(sizeof($ary1), sizeof($ary2)); $i < $len; $i++) {
                if ($ary1[$i] === $ary2[$i]) {
                    continue;
                }
                return $ary1[$i] < $ary2[$i] ? -1 : 1;
            }

            $d_len = sizeof($ary1) - sizeof($ary2);

            return $d_len === 0 ? 0 : ($d_len > 0 ? 1 : -1);
        };

        usort($wnblocks, function ($d1, $d2) use ($recursive_cmp) { return $recursive_cmp($d1['order'], $d2['order']);});

        return $wnblocks;
    }

    public function getXmlNamespace()
    {
        return 'http://moocip.de/schema/block/wallnewspapersidebar/';
    }

    public function getXmlSchemaLocation()
    {
        return 'http://moocip.de/schema/block/wallnewspapersidebar/wallnewspapersidebar-1.0.xsd';
    }


    public function exportContents()
    {
        if (strlen($this->content) === 0) {
            return '';
        }

        $document = new \DOMDocument();
        $document->loadHTML($this->content);

        if ($this->container['version']->newerThan(3.1) || $this->container['wysiwyg_refined']) {
            return \STUDIP\Markup::markAsHtml(\STUDIP\Markup::purify($document->saveHTML()));
        } else {
            return $document->saveHTML();
        }
    }

    public function exportProperties()
    {
        $uuid = '';
        if ($this->wall_newspaper_block_id) {
            if ($block = \Mooc\DB\Block::find($this->wall_newspaper_block_id)) {
                $uuid = $block->getUUID();
            }
        }

        return array('wall_newspaper_block_id' => $uuid);
    }

    public function importContents($contents, array $files)
    {
        $document = new \DOMDocument();
        $document->loadHTML(utf8_decode($contents));

        if ($this->container['version']->newerThan(3.1) || $this->container['wysiwyg_refined']) {
            $this->content = \STUDIP\Markup::markAsHtml(\STUDIP\Markup::purify($document->saveHTML()));
        } else {
            $this->content = $document->saveHTML();
        }
        $this->save();
    }


    public function importProperties(array $properties)
    {
        if (isset($properties['wall_newspaper_block_id'])) {
            $this->wall_newspaper_block_id = $properties['wall_newspaper_block_id'];
        }
    }

    public function importUUIDBlocks($blocks)
    {
        if (strlen($this->wall_newspaper_block_id) && !isset($blocks[$this->wall_newspaper_block_id])) {
            throw new \RuntimeException('Could not find referenced WallNewspaperBlock.');
        }
        $this->wall_newspaper_block_id = $blocks[$this->wall_newspaper_block_id]->id;
        $this->save();
    }
}
