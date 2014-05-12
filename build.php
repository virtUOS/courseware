<?php

require_once 'vendor/autoload.php';

use Assetic\AssetWriter;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Assetic\Filter\LessphpFilter;

$target = 'default';

if (isset($_SERVER['argv'][1])) {
    $target = $_SERVER['argv'][1];
}

switch ($target) {
    case 'zip':
        less();
        zip();
        break;
    default:
        less();
        break;
}

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

function zip()
{
    $archive = new ZipArchive();
    $archive->open('moocip.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    addDirectories($archive, array(
        'assets',
        'blocks',
        'controllers',
        'docs',
        'migrations',
        'models',
        'vendor',
        'views',
    ));
    $archive->addFile('LICENSE');
    $archive->addFile('Mooc.php');
    $archive->addFile('plugin.manifest');
    $archive->addFile('README.md');
    $archive->close();
}

function addDirectory(ZipArchive $archive, $directory)
{
    $archive->addEmptyDir($directory);

    foreach (glob($directory.'/*') as $file) {
        if (is_dir($file)) {
            addDirectory($archive, $file);
        } else {
            $archive->addFile($file);
        }
    }
}

function addDirectories(ZipArchive $archive, array $directories)
{
    foreach ($directories as $directory) {
        addDirectory($archive, $directory);
    }
}
