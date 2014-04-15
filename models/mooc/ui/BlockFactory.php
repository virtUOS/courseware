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
        $class = 'Mooc\\UI\\'.$sorm_block->type.'\\'.$sorm_block->type;

        // there is no class describing a UI for that kind of block
        if (!class_exists($class)) {
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

    // TODO
    public function getContentBlockClasses()
    {
        $all = $this->getBlockClasses();
        return array_diff($all, \Mooc\DB\Block::getStructuralBlockClasses());
    }

    // TODO
    protected function getPluginDir()
    {
        return dirname(dirname(dirname(__DIR__)));
    }
}
