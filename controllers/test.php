<?php

use Mooc\UI\TestBlock\Model\Test;

/**
 * @author Christian Flothmann <christian.flothmann@uos.de>
 */
class TestController extends MoocipController
{
    /**
     * Action providing a list of suggested tests that match a given search term.
     */
    public function suggestion_action()
    {
        header('Content-Type: application/json');
        $this->set_layout(null);
        $this->suggestions = array();
        $tests = Test::findByTerm(Request::get('term'));

        foreach ($tests as $test) {
            $this->suggestions[] = array(
                'value' => $test->id,
                'label' => utf8_encode($test->title),
            );
        }
    }
}
