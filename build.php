<?php

require_once 'vendor/autoload.php';

use Assetic\AssetWriter;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Assetic\Filter\LessphpFilter;

less();

function less()
{
    $lessFiles = new AssetCollection(array(
        new GlobAsset('assets/*.less'),
        new GlobAsset('blocks/*/css/[a-zA-Z0-9]*.less'),
    ), array(new LessphpFilter()));

    foreach ($lessFiles as $lessFile) {
        /** @var FileAsset $lessFile */
        $sourcePath = $lessFile->getSourcePath();
        $lessFile->setTargetPath(substr($sourcePath, 0, strrpos($sourcePath, '.')).'.css');

        $assetWriter = new AssetWriter($lessFile->getSourceRoot());
        $assetWriter->writeAsset($lessFile);
    }

    $lessFiles->ensureFilter(new \Assetic\Filter\CssMinFilter());
    $lessFiles->setTargetPath('moocip.min.css');

    $assetWriter = new AssetWriter('assets');
    $assetWriter->writeAsset($lessFiles);
}
