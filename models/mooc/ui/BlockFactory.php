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
        return dirname(dirname(dirname(__DIR__))) . '/blocks/' . $type;
    }
}
