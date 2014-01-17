<?php
class Courseware_IndexController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        $this->answer = 'yes';
    }
}
