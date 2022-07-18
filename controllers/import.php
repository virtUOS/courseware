<?php

use Mooc\DB\Block;
use Mooc\Export\Validator\XmlValidator;
use Mooc\Import\XmlImport;

/**
 * Controller to import a courseware block tree.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 * @author Ron Lucke <lucke@elan-ev.de>
 */
class ImportController extends CoursewareStudipController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if ($this->container['current_courseware'] === null) {

        }

        if (!$this->container['current_user']->canCreate($this->container['current_courseware'])) {
            throw new Trails_Exception(401);
        }
        Navigation::activateItem('course/mooc_courseware');
    }

    public function index_action()
    {
        $this->errors = array();
        $this->warnings = array();

        // upload filed
        if (Request::method() == 'POST' && Request::option('subcmd')=='upload') {
            if (count($this->errors) === 0) {
                $this->installModule($_FILES['import_file']['tmp_name']);
            }
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
    private function validateUploadFile($tempDir, array &$errors, array &$warnings)
    {
        $dataFile = $tempDir.'/data.xml';

        if (!is_file($dataFile)) {
            $errors[] = _cw('Import-Archiv enthält keine Datendatei data.xml.');

            return false;
        }

        $validator = new XmlValidator($this->plugin->getBlockFactory());
        $validationErrors = $validator->validate(file_get_contents($dataFile));

        if (count($validationErrors) > 0) {
            foreach ($validationErrors as $validationError) {
                switch ($validationError->code){
                    case 1878: // handle unknown blocks
                        $warnings[] = $validationError->message;
                        break;
                    case 1824: // invalid filesize - no int value
                    case 1831: // empty filename
                        if (strpos($validationError->message, 'file') > -1) {
                            $warnings[] = $validationError->message;
                        } else {
                            $errors[] = $validationError->message;
                        }
                        break;
                    default:
                        $errors[] = $validationError->message;
                }
            }
            if (!empty($warnings)){
                array_unshift($warnings, _cw('Es konnten möglicherweise nicht alle Blöcke importiert werden'));
            }
            if (!empty($errors)){
                array_unshift($errors, _cw('Die Datendatei data.xml enthält kein valides XML.'));

                return false;
            }
        }

        return true;
    }

    private function installModule($filename)
    {
        $this->success = false;
        if (!$filename) {
            $this->errors[] = _cw('Es wurde kein Import-Archiv ausgewählt.');
            return false;
        }
        // create a temporary directory
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);
        $extracted = Studip\ZipArchive::extractToPath($filename, $tempDir);
        if (!$extracted) {
            $this->errors[] = _cw('Das Import-Archiv ist beschädigt.');
            return false;
        }

        $root_folder = Folder::findTopFolder($GLOBALS['SessionSeminar']);
        $parent_folder = FileManager::getTypedFolder($root_folder->id);
        // create new folder for import
        $request = array('name' => 'Courseware-Import '.date("d.m.Y", time()), 'description' => 'folder for imported courseware content');
        $new_folder = new StandardFolder();
        $new_folder->setDataFromEditTemplate($request);
        $new_folder->user_id = User::findCurrent()->id;
        $courseware_folder = $parent_folder->createSubfolder($new_folder);

        $install_folder = FileManager::getTypedFolder($courseware_folder->id);

        if ($this->validateUploadFile($tempDir, $this->errors, $this->warnings)) {
            $courseware = $this->container['current_courseware'];
            $importer = new XmlImport($this->plugin->getBlockFactory());
            $redirect = true;
            try {
                $importer->import($tempDir, $courseware, $install_folder);
            } catch (Exception $e){
                $this->errors[] = $e;
                $redirect = false;
            }
            if (!empty($this->warnings)) {
                $redirect = false;
            }
            if($redirect){
                // $this->redirect(PluginEngine::getURL($this->plugin, array(), 'courseware'));
                $this->success = true;
            }
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
