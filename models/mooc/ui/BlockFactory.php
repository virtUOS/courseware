<?php
namespace Mooc\UI;

/**
 * TODO
 *
 * @author  <mlunzena@uos.de>
 */
class BlockFactory {

    private $container;

    // TODO
    public function __construct(\Mooc\Container $container)
    {
        $this->container = $container;
    }

    // TODO
    public function makeBlock($sorm_block)
    {
        $class = $this->loadBlock($sorm_block->type);

        // there is no class describing a UI for that kind of block
        if (!$class) {
            return null;
        }

        return new $class($this->container, $sorm_block);
    }

    // TODO
    public function getBlockClasses()
    {
        static $classes;
        if (!isset($classes)) {
            $classes = array_map("basename", glob($this->getPluginDir() . '/blocks/*'));
        }
        return $classes;
    }


    private static $structural_block_classes = array('Courseware', 'Chapter', 'Subchapter', 'Section');

    public function getContentBlockClasses()
    {
        $all = $this->getBlockClasses();
        return array_diff($all, self::$structural_block_classes);
    }

    // TODO
    private function loadBlock($type)
    {
        $class = sprintf('Mooc\\UI\\%s', $type);
        if (!class_exists($class, false)) {

            $file = $this->getBlockDir($type) . '/' . $type . '.php';
            if (!file_exists($file)) {
                return null;
            }

            require_once $file;
        }
        return $class;
    }

    // TODO
    protected function getBlockDir($type)
    {
        return $this->getPluginDir() . '/blocks/' . $type;
    }

    // TODO
    protected function getPluginDir()
    {
        return dirname(dirname(dirname(__DIR__)));
    }
}
