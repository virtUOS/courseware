<?php

namespace Mooc\UI\BlubberBlock;

use Mooc\UI\Block;
use Mooc\UI\Section\Section;

/**
 * Display the contents of a Blubber stream in a (M)ooc.IP block.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class BlubberBlock extends Block
{
    const NAME = 'Diskussion';

    public function student_view()
    {
        if (!$active = $this->blubberActivated()) {
            return compact('active');
        }

        // on view: grade with 100%
        $this->setGrade(1.0);

        $streamUrl = \PluginEngine::getURL(
            $GLOBALS['plugin'],
            array(),
            'blubber/index/'.$this->id
        );

        $pluginManager = \PluginManager::getInstance();
        $blubberPlugin = $pluginManager->getPlugin('Blubber');
        $assetsBaseUrl = $blubberPlugin->getPluginUrl(). '/assets/';

        return array(
            'active'          => true,
            'stream_url'      => $streamUrl,
            'assets_base_url' => $assetsBaseUrl,
        );
    }

    public function author_view()
    {
        if (!$active = $this->blubberActivated()) {
            return compact('active');
        }

        return compact('active');
    }

    /**
     * {@inheritDoc}
     */
    public function isEditable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function additionalInstanceAllowed(Section $section, $subType = null)
    {
        $blubberBlockAllowed = true;
        $section->traverseChildren(function ($child) use (&$blubberBlockAllowed) {
            if ($child instanceof BlubberBlock) {
                $blubberBlockAllowed = false;
            }
        });

        return $blubberBlockAllowed;
    }

    private function blubberActivated()
    {
        $plugin_manager = \PluginManager::getInstance();
        $plugin_info = $plugin_manager->getPluginInfo('Blubber');
        return $plugin_manager->isPluginActivated($plugin_info['id'], $this->getModel()->seminar_id);
    }
}
