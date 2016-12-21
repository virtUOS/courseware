<?php

require_once 'vendor/autoload.php';

use Assetic\AssetWriter;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\GlobAsset;
use Assetic\Filter\CssMinFilter;
use Assetic\Filter\LessphpFilter;

$target = 'default';

if (isset($_SERVER['argv'][1])) {
    $target = $_SERVER['argv'][1];
}

switch ($target) {
    case 'zip':
        less();
        locales();
        zip();
        break;
    case 'watch':
        $timeout = 5;

        if (isset($_SERVER['argv'][2]) && $_SERVER['argv'][2] > 0) {
            $timeout = (int) $_SERVER['argv']['timeout'];
        }

        watch($timeout);
        break;
    default:
        less();
        locales();
        break;
}

/**
 * Compiles LESS files to CSS files.
 */
function less()
{
    dumpAssets(getAssets());

    printSuccess('compiled LESS files');
}

/**
 * Creates the Stud.IP plugin zip archive.
 */
function zip()
{
    $archive = new ZipArchive();
    $archive->open('courseware.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
    addDirectories($archive, array(
        'assets',
        'blocks',
        'controllers',
        'cronjobs',
        'docs',
        'export',
        'import',
        'locale',
        'migrations',
        'models',
        'vendor',
        'views',
    ), '/^(assets|blocks).*\.less$/');
    $archive->addFile('LICENSE');
    $archive->addFile('Courseware.php');
    $archive->addFile('CoursewareObserver.php');
    $archive->addFile('plugin.manifest');
    $archive->addFile('README.md');
    $archive->close();

    printSuccess('created the Stud.IP plugin zip archive');
}

/**
 * Watch for changes in LESS files and compile them on demand.
 *
 * @param int $timeout The timeout between two watch cycles
 */
function watch($timeout)
{
    printSuccess('watching for changes in LESS files to be compiled');
    $assets = getAssets();

    while (true) {
        $updateNeeded = false;

        foreach ($assets as $asset) {
            /** @var AssetInterface $asset */

            if (assetNeedsUpdate($asset)) {
                printInfo($asset->getSourceRoot().'/'.$asset->getSourcePath().' has changed');
                $updateNeeded = true;
            }
        }

        if ($updateNeeded) {
            dumpAssets($assets);
            printSuccess('compiled changed LESS files');
        }

        sleep($timeout);
    }
}


function locales()
{
    $output = array();

    $fd = fopen('locale/js.pot', 'w');

    fputs($fd, 'msgid ""' ."\n"
        . 'msgstr ""' . "\n"
        . '"Project-Id-Version: PACKAGE VERSION\n"' . "\n"
        . '"Report-Msgid-Bugs-To: \n"' . "\n"
        . '"POT-Creation-Date: ' . date('Y-m-md H:i') .'+0200\n"' . "\n"
        . '"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"' . "\n"
        . '"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"' . "\n"
        . '"Language-Team: LANGUAGE <LL@li.org>\n"' . "\n"
        . '"Language: \n"' . "\n"
        . '"MIME-Version: 1.0\n"' . "\n"
        . '"Content-Type: text/plain; charset=ISO-8859-1\n"' . "\n"
        . '"Content-Transfer-Encoding: 8bit\n"' ."\n\n");

    // collect translatable texts
    exec("for i in blocks/*/templates/*mustache; do iconv -c -f cp1252 \$i | awk '{if (match($0, /i18n}}([^{]*){{/)) {print substr($0, RSTART+6, RLENGTH-8)}}'; done | sort -u", $output);
    exec("for i in blocks/*/*/*js; do iconv -c -f utf-8 \$i | awk '{if (match($0, /i18n([^{]*)\")/)) {print substr($0, RSTART+6, RLENGTH-8)}}'; done | sort -u", $output);

    $output[] = 'Bestätigung';
    $output[] = 'Diskussion';
    $output[] = 'Evaluationen';
    $output[] = 'Freitext';

    foreach ($output as $entry) {
        if (strlen($entry)) {
            fputs($fd, 'msgid "'. str_replace('"', '\\"', utf8_decode($entry)) .'"' . "\n");
            fputs($fd, 'msgstr ""' ."\n\n");
        }
    }

    fclose($fd);

    exec('make -f locale/Makefile');
}


/**
 * Returns the collection of assets that need to be processed.
 *
 * @return AssetCollection The assets
 */
function getAssets()
{
    $assets = new AssetCollection(
        array(
            new GlobAsset('assets/*.less'),
            new GlobAsset('blocks/*/css/[a-zA-Z0-9]*.less'),
        ),
        array(new LessphpFilter())
    );

    foreach ($assets as $asset) {
        /** @var AssetInterface $asset */
        $sourcePath = $asset->getSourcePath();
        $asset->setTargetPath(substr($sourcePath, 0, strrpos($sourcePath, '.')).'.css');
    }

    $assets->setTargetPath('courseware.min.css');

    return $assets;
}

/**
 * Checks whether or not an asset needs to be compiled.
 *
 * @param AssetInterface $asset The asset to check
 *
 * @return bool True, if the asset is not up-to-date, false otherwise
 */
function assetNeedsUpdate(AssetInterface $asset)
{
    $targetFile = $asset->getSourceRoot().'/'.$asset->getTargetPath();

    if (!is_file($targetFile)) {
        return true;
    }

    return filemtime($targetFile) < $asset->getLastModified();
}

/**
 * Dumps a collection of assets.
 *
 * @param AssetCollection $assets The assets to dump
 */
function dumpAssets(AssetCollection $assets)
{
    foreach ($assets as $asset) {
        /** @var AssetInterface $asset */
        $assetWriter = new AssetWriter($asset->getSourceRoot());

        try {
            $assetWriter->writeAsset($asset);
        } catch (Exception $e) {
            printError($e->getMessage());
        }
    }

    // apply the CSS min filter only to the moocip.min.css file
    $assets = clone $assets;
    $assets->ensureFilter(new CssMinFilter());
    $assetWriter = new AssetWriter('assets');

    try {
        $assetWriter->writeAsset($assets);
    } catch (Exception $e) {
        printError($e->getMessage());
    }
}

/**
 * Recursively adds a directory tree to a zip archive.
 *
 * @param ZipArchive $archive           The zip archive
 * @param string     $directory         The directory to add
 * @param string     $ignoredFilesRegex Regular expression that matches
 *                                      files which should be ignored
 */
function addDirectory(ZipArchive $archive, $directory, $ignoredFilesRegex = '')
{
    $archive->addEmptyDir($directory);

    foreach (glob($directory.'/*') as $file) {
        if (is_dir($file)) {
            addDirectory($archive, $file, $ignoredFilesRegex);
        } else {
            if ($ignoredFilesRegex === '' || !preg_match($ignoredFilesRegex, $file)) {
                $archive->addFile($file);
            } else {
                printError('ignore '.$file);
            }
        }
    }
}

/**
 * Recursively adds directory trees to a zip archive.
 *
 * @param ZipArchive $archive           The zip archive
 * @param array      $directories       The directories to add
 * @param string     $ignoredFilesRegex Regular expression that matches
 *                                      files which should be ignored
 */
function addDirectories(ZipArchive $archive, array $directories, $ignoredFilesRegex = '')
{
    foreach ($directories as $directory) {
        addDirectory($archive, $directory, $ignoredFilesRegex);
    }
}

/**
 * Prints a success message to the standard output stream of the console.
 *
 * @param string $message The message to print
 */
function printSuccess($message)
{
    echo "\033[32m".$message."\033[39m".PHP_EOL;
}

/**
 * Prints an info message to the standard output stream of the console.
 *
 * @param string $message The message to print
 */
function printInfo($message)
{
    echo $message.PHP_EOL;
}

/**
 * Prints an error message to the standard output stream of the console.
 *
 * @param string $message The message to print
 */
function printError($message)
{
    file_put_contents('php://stderr', "\033[31m".$message."\033[39m".PHP_EOL);
}
