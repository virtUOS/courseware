<?php
namespace Mooc\UI;

/**
 * TODO
 *
 * @author  <mlunzena@uos.de>
 */
class BlockFactory {

    // TODO
    public function makeBlock($sorm_block)
    {
        $class = $this->loadBlock($sorm_block->type);

        $ui_block = new $class($sorm_block);

        return $ui_block;
    }

    // TODO
    private function loadBlock($type)
    {
        $class = sprintf('Mooc\\UI\\%s', $type);
        if (!class_exists($class)) {
            $file = $this->getBlockDir($type) . '/' . $type . '.php';
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
