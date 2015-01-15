<?php

use Mooc\DB\Block;
use Mooc\Export\Validator\XmlValidator;
use Mooc\Import\XmlImport;

/**
 * Controller to import a courseware block tree.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class ImportController extends MoocipController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }


    public function index_action()
    {
        $this->errors = array();

        // upload filed
        if (Request::method() == 'POST' && Request::option('subcmd')=='upload') {
            if (count($this->errors) === 0) {
                $this->installModule($_FILES['import_file']['tmp_name']);
            }

        // search for content modules from marketplace
        } else if (Request::method() == 'POST' && Request::option('subcmd')=='search') {
            require_once('lib/plugins/engine/PluginRepository.class.php');
            $repo = new PluginRepository('http://content.moocip.de/?dispatch=xml');
            $this->modules = $repo->getPlugins(Request::option('q'));

        // search for content modules from marketplace
        } else if (Request::method() == 'POST' && Request::option('subcmd')=='install') {
            $temp_name = tempnam(get_config('TMP_PATH'), 'module');
            require_once('lib/plugins/engine/PluginRepository.class.php');
            $repo = new PluginRepository('http://content.moocip.de/?dispatch=xml');
            $module=$repo->getPlugin(Request::quoted('n'));
            if (!@copy($module['url'], $temp_name)) {
                $this->msg = _('Das Herunterladen des Moduls ist fehlgeschlagen.');
            }
            $this->installModule($temp_name);
        }

        if (Navigation::hasItem('/course/mooc_courseware/import')) {
            Navigation::activateItem("/course/mooc_courseware/import");
        }
    }

    /**
     * @param string $tempDir The temporary directory where the archive has
     *                        been extracted to
     * @param array  $errors  Possible error messages will be written to
     *                        this array
     *
     * @return bool True if the import archive is valid, false otherwise
     */
    private function validateUploadFile($tempDir, array &$errors)
    {
        $dataFile = $tempDir.'/data.xml';

        if (!is_file($dataFile)) {
            $errors[] = _('Import-Archiv enthält keine Datendatei data.xml.');

            return false;
        }

        $validator = new XmlValidator($this->plugin->getBlockFactory());
        $validationErrors = $validator->validate(file_get_contents($dataFile));

        if (count($validationErrors) > 0) {
            $errors[] = _('Die Datendatei data.xml enthält kein valides XML.');

            foreach ($validationErrors as $validationError) {
                $errors[] = $validationError;
            }

            return false;
        }

        return true;
    }

    private function installModule($filename) {
        // create a temporary directory
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);
        unzip_file($filename, $tempDir);

        if ($this->validateUploadFile($tempDir, $this->errors)) {
            $coursewareBlock = Block::findCourseware(Request::get('cid'));
            $courseware = $this->plugin->getBlockFactory()->makeBlock($coursewareBlock);
            $importer = new XmlImport($this->plugin->getBlockFactory());
            $importer->import($tempDir, $courseware);

            $this->redirect(PluginEngine::getURL($this->plugin, array(), 'courseware'));
        }

        $this->deleteRecursively($tempDir);

    }

    private function deleteRecursively($path)
    {
        if (is_dir($path)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                /** @var SplFileInfo $file */
                if (in_array($file->getBasename(), array('.', '..'))) {
                    continue;
                }

                if ($file->isFile() || $file->isLink()) {
                    unlink($file->getRealPath());
                } else if ($file->isDir()) {
                    rmdir($file->getRealPath());
                }
            }

            rmdir($path);
        } else if (is_file($path) || is_link($path)) {
            unlink($path);
        }
    }
}
