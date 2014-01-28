<?php

require_once 'moocip_controller.php';

class RegistrationsController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->cid = Request::option('moocid');
    }

    public function index_action()
    {
        Navigation::activateItem("/mooc/registrations");

        $this->course = \Course::find($this->cid);
    }
    
    public function create_action()
    {
        Navigation::activateItem("/mooc/registrations");

        $this->course = Course::find($this->cid);

        if (!Request::option('accept_tos')) {
            throw new Exception('You need to accept the TOS!');
        } else {
            #TODO: check if mail adress is valid, use Stud.IP-API if possible
            $mail = Request::get('mail');

            // add user to database
            $password = str_replace('0', 'o', substr(\base_convert(\uniqid('pass', true), 10, 36), 1, 8));

            $data = array(
                'username' => $mail,
                'perms'    => 'autor',
                'Vorname'  => Request::get('vorname'),
                'Nachname' => Request::get('nachname'),
                'Email'    => $mail,
                'Password' => UserManagement::getPwdHasher()->HashPassword($password)
            );
            
            $user = User::create($data);
            
            $userinfo = new Userinfo($user->getId());
            $userinfo->store();
            
            // send mail with password to user
            StudipMail::sendMessage($mail, 
                sprintf(_('Zugang zum MOOC-Kurs "%s"'), $this->course->name),
                sprintf(_("Ihre Zugangsdaten für den MOOC-Kurs \"%s\":\n\n"
                        . "Benutzername: %s \n"
                        . "Passwort: %s \n\n"
                        . "Hier kommen Sie direkt ins System:\n%s"),
                        $this->course->name, $mail, $password, $GLOBALS['ABSOLUTE_URI_STUDIP']
                )
            );
            
            // add user to seminare
            $new = new CourseMember(array($this->cid, $user->getId()));
            if ($new->isNew()) {
                $new->status = 'autor';
                $new->admission_studiengang_id = 'all';
                $new->label = '';
            }
            $new->store();       
        }
        
        $this->registered = true;
        
        $this->render_action('index');
    }
}
