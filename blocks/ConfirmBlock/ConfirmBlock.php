<?php

namespace Mooc\UI\ConfirmBlock;

use Mooc\UI\Block;

/**
 */
class ConfirmBlock extends Block
{
    const NAME = 'Bestätigung';

    function initialize()
    {
        if ($this->title === 'Ein weiterer ConfirmBlock') {
            $this->title = 'Ich habe den Abschnitt gelesen.';
        }
    }

    function student_view()
    {
        $courseware = $this->container['current_courseware'];
        $next_chapter_id = $courseware->getNeighborSections($this->_model->parent)["next"]["id"];
        $next_chapter_link = \PluginEngine::getURL(Courseware, array('selected' => $next_chapter_id), "courseware");
        return array(
            'confirmed' => !! $this->getProgress()->grade,
            'title'     => $this->title,
            'next_chapter_link' => $next_chapter_link 
        );
    }

    function author_view()
    {
        $this->authorizeUpdate();

        return array(
            'title' => $this->title
        );
    }

    // this is called when any user checks the checkbox
    // set the grade to 100%
    function confirm_handler($data)
    {
        $this->setGrade(1);
        return array();
    }
}
