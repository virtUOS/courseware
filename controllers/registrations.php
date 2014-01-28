<?php

require_once 'moocip_controller.php';

class RegistrationsController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->cid = $this->plugin->getContext();
    }

    public function index_action()
    {
        Navigation::activateItem("/course/mooc_registrations");

        $this->course = \Course::find($this->cid);
    }
    
    public function create_function()
    {
        
    }
}
