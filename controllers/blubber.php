<?php

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class BlubberController extends MoocipController
{
    public function index_action($blockId)
    {
        if (Request::isXhr()) {
            $this->set_layout(null);
        } else {
            if (Navigation::hasItem('/course/discussion')) {
                Navigation::activateItem('/course/discussion');
            }

            $pluginManager = PluginManager::getInstance();
            $blubberPlugin = $pluginManager->getPlugin('Blubber');
            $this->assets_url = $blubberPlugin->getPluginUrl(). '/assets/';
            PageLayout::addHeadElement('link',
                array(
                    'href' => $this->assets_url.'stylesheets/blubberforum.css',
                    'rel' => 'stylesheet'
                ),
                ''
            );
            PageLayout::addHeadElement('script', array('src' => $this->assets_url.'/javascripts/autoresize.jquery.min.js'), '');
            PageLayout::addHeadElement('script', array('src' => $this->assets_url.'/javascripts/blubber.js'), '');
            PageLayout::addHeadElement('script', array('src' => $this->assets_url.'/javascripts/formdata.js'), '');
        }

        // retrieve all blubber threads to display
        $courseStream = BlubberStream::getCourseStream(Request::get('cid'));
        $courseStream['filter_hashtags'] = array('block-'.$blockId);
        $this->threads = $courseStream->fetchThreads(0, 11);
    }
}
