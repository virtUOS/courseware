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

        if (Request::method() == 'POST') {
            if (count($this->errors) === 0) {
                // create a temporary directory
                $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
                mkdir($tempDir);

                unzip_file($_FILES['import_file']['tmp_name'], $tempDir);

                if ($this->validateUploadFile($tempDir, $this->errors)) {
                    $coursewareBlock = Block::findCourseware(Request::get('cid'));
                    $courseware = $this->container['block_factory']->makeBlock($coursewareBlock);
                    $importer = new XmlImport($this->container['block_factory']);
                    $importer->import(file_get_contents($tempDir.'/data.xml'), $courseware);

                    $this->redirect(PluginEngine::getURL($this->plugin, array(), 'courseware'));
                }
            }
        }

        Navigation::activateItem("/course/mooc_courseware/import");
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

        $validator = new XmlValidator($this->container['block_factory']);

        if (!$validator->validate(file_get_contents($dataFile))) {
            $errors[] = _('Die Datendatei data.xml enthält kein valides XML.');

            return false;
        }

        return true;
    }
}
