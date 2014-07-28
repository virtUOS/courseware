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
            if (!static::vipsExists()) {
                return null;
            }

            if (static::$vipsPlugin === null) {
                static::$vipsPlugin = new \VipsPlugin();
            }

            return static::$vipsPlugin;
        }

        public static function vipsExists()
        {
            return class_exists('\VipsPlugin');
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
    }
}

namespace {
    use Mooc\UI\TestBlock\Vips\Bridge;

    if (Bridge::vipsExists()) {
        require_once Bridge::getVipsPath().'/vips_assignments.inc.php';
    }
}
