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
        return array(
            'confirmed' => !! $this->getProgress()->grade,
            'title'     => $this->title
        );
    }

    function author_view()
    {
        return array(
            'title' => $this->title
        );
    }

    // this is called when the user checks the checkbox
    // set the grade to 100%
    function confirm_handler($data)
    {
        $this->setGrade(1);
        return array();
    }
}
