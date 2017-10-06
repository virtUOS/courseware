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
    const NAME = 'Blubber';

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }
        if (!$active = self::blubberActivated($this)) {
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
        $this->authorizeUpdate();

        if (!$active = self::blubberActivated($this)) {
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
    public static function additionalInstanceAllowed($container, Section $section, $subType = null)
    {
        // deactivate new Blubber blocks
        // see https://github.com/virtUOS/courseware/issues/31
        return false;

        if (!self::blubberActivated($section)) {
            return false;
        }

        $blubberBlockAllowed = true;
        $section->traverseChildren(function ($child) use (&$blubberBlockAllowed) {
            if ($child instanceof BlubberBlock) {
                $blubberBlockAllowed = false;
            }
        });

        return $blubberBlockAllowed;
    }

    // is the Blubber plugin activated in the currently selected course
    private static function blubberActivated($block)
    {
        $plugin_manager = \PluginManager::getInstance();
        $plugin_info = $plugin_manager->getPluginInfo('Blubber');
        return $plugin_manager->isPluginActivated($plugin_info['id'], $block->getModel()->seminar_id);
    }
}
