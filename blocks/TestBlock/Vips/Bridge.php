<?php

namespace Mooc\UI\TestBlock\Vips {

    /**
     * @author Christian Flothmann <christian.flothmann@uos.de>
     */
    class Bridge
    {
        /**
         * @var \VipsPlugin
         */
        private static $vipsPlugin;

        /**
         * Returns the path to the Vips Plugin.
         *
         * @return string The path to the Vips Plugin
         */
        public static function getVipsPath()
        {
            $plugin = self::getVipsPlugin();

            if ($plugin === null) {
                return null;
            }

            return $plugin->getPluginPath();
        }

        /**
         * Returns an instance of Vips Plugin class.
         *
         * @return \VipsPlugin The Vips Plugin instance
         */
        public static function getVipsPlugin()
        {
            if (static::$vipsPlugin === null) {
                static::$vipsPlugin = \PluginEngine::getPlugin('VipsPlugin');
                if (static::$vipsPlugin) {
                    \PageLayout::addStylesheet(static::$vipsPlugin->getPluginURL() .'/css/vips_style.css');
                    \PageLayout::addHeadElement('script', $attributes,
                        "VIPS_CHARACTER_PICKER = '". vips_url('sheets/get_character_picker_ajax') . "';");
                    \PageLayout::addScript(static::$vipsPlugin->getPluginURL() .'/js/character_picker.js');
                }
            }

            return static::$vipsPlugin;
        }

        // predicate checking for an activated VipsPlugin
        public static function vipsExists()
        {
            return !!self::getVipsPlugin();
        }

        /**
         * @param \Mooc\UI\Block $block  a block of the courseware
         * @return bool   TRUE if Vips is activated in the course
         *                to which the given block belongs.
         */
        public static function vipsActivated(\Mooc\UI\Block $block)
        {
            if (!static::vipsExists()) {
                return false;
            }

            $plugin_manager = \PluginManager::getInstance();
            $plugin_info = $plugin_manager->getPluginInfo('VipsPlugin');
            return $plugin_manager->isPluginActivated($plugin_info['id'], $block->getModel()->seminar_id);
        }

        /**
         * Returns the next position of a test in a course.
         *
         * @param string $courseId The course id
         *
         * @return int The calculated position (1 or higher)
         */
        public static function findNextVipsPosition($courseId)
        {
            $db = \DBManager::get();
            $stmt = $db->prepare(
                'SELECT
                  COUNT(*)
                FROM
                  vips_test
                WHERE
                  course_id = :course_id'
            );
            $stmt->bindValue(':course_id', $courseId);
            $stmt->execute();

            return ((int) $stmt->fetchColumn(0)) + 1;
        }

        /**
         * Creates a Vips exercise instance.
         *
         * @param string $type The exercise type
         * @param string $xml  The exercise as an XML formatted string
         * @param string $id   The exercise id
         *
         * @return \Exercise The exercise instance
         */
        public static function getExerciseInstance($type, $xml, $id)
        {
            $path = static::getVipsPath().'/exercises/'.$type.'.php';

            if (file_exists($path)) {
                require_once $path;

                return new $type($xml, $id);
            }

            return null;
        }
    }
}

namespace {
    use Mooc\UI\TestBlock\Vips\Bridge;

    if (Bridge::vipsExists()) {
        require_once Bridge::getVipsPath().'/vips_assignments.inc.php';
    }
}
