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
    
    public function create_action()
    {
        if (!Request::option('tos')) {
            throw new Exception('You need to accept the TOS!');
        } else {
            $data = array(
                'username' => Request::get('mail'),
                'Vorname'  => Request::get('vorname'),
                'Nachname' => Request::get('nachname'),
            );
            
            $user = User::create($data);

            $um = new UserManagement($user->getId());
            $um->setPassword();
            
            //$course = Course::find($this->cid);
            //$course->members->
        }        
    }
}
