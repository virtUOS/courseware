<?php

namespace Mooc\UI\ConfirmBlock;

use Mooc\UI\Block;

class ConfirmBlock extends Block
{
    const NAME = 'Bestätigung';
    const BLOCK_CLASS = 'function';
    const DESCRIPTION = 'Vom Lernenden bestätigen lassen, dass der Inhalt betrachtet wurde';

    public function initialize()
    {
        if ($this->title === 'Ein weiterer ConfirmBlock') {
            $this->title = 'Ich habe den Abschnitt gelesen.';
        }
    }

    public function student_view()
    {
        if (!$this->isAuthorized()) {
            return array('inactive' => true);
        }

        return array(
            'confirmed' => !! $this->getProgress()->grade,
            'title'     => $this->title
        );
    }

    public function author_view()
    {
        $this->authorizeUpdate();

        return array(
            'title' => $this->title
        );
    }

    public function preview_view()
    {

        return;
    }

    // this is called when any user checks the checkbox
    // set the grade to 100%
    public function confirm_handler($data)
    {
        $this->setGrade(1);

        return array();
    }

    public function getPdfExportData()
    {
        $data = $this->student_view();

        $unconfirmed = 'https://develop.studip.de/studip/assets/images/icons/blue/checkbox-unchecked.svg';
        $confirmed = 'https://develop.studip.de/studip/assets/images/icons/blue/checkbox-checked.svg';

        return sprintf('<img src="%s"> %s', $data['confirmed'] ? $confirmed : $unconfirmed, $data['title']);
    }

    public function getHtmlExportData()
    {
        return ;
    }
}
