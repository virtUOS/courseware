<?php

namespace Mooc\TestBlock\Vips {

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
            $path = self::getVipsPlugin()->getPluginPath();
            return $path;
        }

        /**
         * Returns an instance of Vips Plugin class.
         *
         * @return \VipsPlugin The Vips Plugin instance
         */
        public static function getVipsPlugin()
        {
            if (static::$vipsPlugin === null) {
                static::$vipsPlugin = new \VipsPlugin();
            }

            return static::$vipsPlugin;
        }
    }
}

namespace {
    use Mooc\TestBlock\Vips\Bridge;

    require_once Bridge::getVipsPath().'/vips_assignments.inc.php';
}
