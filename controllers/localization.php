<?php

class LocalizationController extends CoursewareStudipController
{
    public function index_action()
    {
        $this->set_content_type('application/javascript; charset=UTF-8');

        $modified = filemtime(dirname(__FILE__) . '/../views/localization/index.php');
        $this->response->add_header('Last-Modified', date("r", $modified));

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            if (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $modified) {
                $this->set_status(304, "Not modified.");
                $this->render_nothing();

                return;
            }
        }

        // load translatable texts
        $cache = StudipCacheFactory::getCache();

        $cache->expire('courseware/translatables');

        if (!$this->translatable_texts = unserialize($cache->read('courseware/translatables'))) {
            $this->translatable_texts = array();

            // read the text strings that are (potentially) used in JS
            $fd = fopen(__DIR__ .'/../locale/en/LC_MESSAGES/courseware.po', 'r');
            while ($line = fgets($fd)) {
                if (strpos($line, "msgid") !== false) {
                    $line = str_replace("\n", '', $line);
                    $line = str_replace("\r", '', $line);
                    $line = str_replace('\"', '"', $line);
                    $line = str_replace('msgid "', '', $line);
                    $line = rtrim($line, '"');

                    if ($line) {
                        $this->translatable_texts[] = utf8_encode($line);
                    }
                }
            }
            $cache->write('courseware/translatables', serialize($this->translatable_texts));
        }

        $this->layout = null;
        //$this->language = str_replace('.UTF-8', '', $_SESSION['_language']);

        // make this instance available to the view to use
        // the helper methods
        $this->plugin = $this;
    }
}
