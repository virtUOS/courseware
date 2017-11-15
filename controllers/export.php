<?php

use Mooc\Export\XmlExport;

/**
 * Controller to export a courseware block tree.
 *
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class ExportController extends CoursewareStudipController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!$this->container['current_user']->canCreate($this->container['current_courseware'])) {
            throw new Trails_Exception(401);
        }
    }

    public function index_action()
    {
        // create a temporary directory
        $tempDir = $GLOBALS['TMP_PATH'].'/'.uniqid();
        mkdir($tempDir);

        // dump the XML to the filesystem
        $export = new XmlExport($this->plugin->getBlockFactory());
        $courseware = $this->container['current_courseware'];
        foreach ($courseware->getFiles() as $file) {
            if (trim($file['url']) !== '') {
                continue;
            }

            $destination = $tempDir . '/' . $file['id'];
            mkdir($destination);
            copy($file['path'], $destination.'/'.$file['filename']);
        }
        if (Request::submitted('plaintext')) {
            $this->response->add_header('Content-Type', 'text/xml;charset=utf-8');
            $this->render_text($export->export($courseware));
            return;
        }
        file_put_contents($tempDir.'/data.xml', $export->export($courseware));

        $zipFile = $GLOBALS['TMP_PATH'].'/'.uniqid().'.zip';
        create_zip_from_directory($tempDir, $zipFile);
        $this->set_layout(null);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=courseware.zip');

        while (ob_get_level()) {
            ob_end_flush();
        }
        readfile($zipFile);

        $this->deleteRecursively($tempDir);
        $this->deleteRecursively($zipFile);

        exit;
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
 
