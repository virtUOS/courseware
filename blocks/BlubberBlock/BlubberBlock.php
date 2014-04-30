<?php

namespace Mooc\UI\BlubberBlock;

use Mooc\UI\Block;

/**
 * Display the contents of a Blubber stream in a (M)ooc.IP block.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class BlubberBlock extends Block
{
    const NAME = 'Diskussion';

    public function student_view()
    {
        $streamUrl = \PluginEngine::getURL(
            $GLOBALS['plugin'],
            array(),
            'blubber/index/'.$this->id
        );

        $pluginManager = \PluginManager::getInstance();
        $blubberPlugin = $pluginManager->getPlugin('Blubber');
        $assetsBaseUrl = $blubberPlugin->getPluginUrl(). '/assets/';

        return array(
            'stream_url' => $streamUrl,
            'assets_base_url' => $assetsBaseUrl,
        );
    }

    public function author_view()
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function isEditable()
    {
        return false;
    }
}
