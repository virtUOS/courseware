<?php

require_once 'moocip_controller.php';

class RegistrationsController extends MoocipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->cid = Request::option('moocid');
    }

    public function new_action()
    {
        Navigation::activateItem("/mooc/registrations");

        $this->course = \Course::find($this->cid);
    }

    public function create_action()
    {
        $this->course = Course::find($this->cid);

        if (!Request::option('accept_tos')) {
            return $this->error(_('Sie müssen die Nutzungsbedingungen akzeptieren!'), 'registrations/new');
        }

        switch (Request::get('type')) {
            default:
            case 'register':
                return $this->register();

            case 'login':
                return $this->loginAndRegister();

            case 'create':
                return $this->createAccountAndRegister();
        }
    }

    function show_action($user_id)
    {
        if (Request::get('username')) {
            $this->loginUser();
            $this->redirect('courses/show/' . $this->cid . '?cid=' . $this->cid);
        } else {
            Navigation::activateItem("/mooc/registrations");
            PageLayout::addScript($this->plugin->getPluginURL().'/assets/js/registrations.js');

            $this->course = \Course::find($this->cid);
            $this->user   = User::find($user_id);
        }
    }
    
    function resend_mail_action($user_id)
    {
        $course = \Course::find($this->cid);
        $user   = User::find($user_id);
        
        if ($_SESSION['mooc']['register']['username'] == $user->username) {
            $this->sendMail($course, $user->username, $_SESSION['mooc']['register']['password']);
            $this->render_json(array('message' => _('Die Bestätigungsmail wurde erfolgreich erneut versendet!')));
        } else {
            throw new Trails_Exception(400, 'Invalid session');
        }
    }

    /*******************/
    /* PRIVATE METHODS */
    /*******************/


    private function register()
    {
        global $user;

        if ($user->id === 'nobody') {
            return $this->error('Nicht angemeldet!', 'registrations/new');
        }

        $this->registerUserWithCourse($user, $this->cid);

        $this->redirect('courses/show/' . $this->cid . '?cid=' . $this->cid);
    }

    private function loginAndRegister()
    {
        $user = $this->loginUser();
        if (!$user) {
            return $this->error('Fehler beim Anmelden', 'registrations/new');
        }

        $this->registerUserWithCourse($user, $this->cid);

        $this->redirect('courses/show/' . $this->cid . '?cid=' . $this->cid);
    }

    private function createAccountAndRegister()
    {
        try {
            $user = $this->createAccount();
        } catch (Exception $e) {
            return $this->error('Fehler beim Anlegen des Accounts: ' . htmlReady($e->getMessage()), 'registrations/new');
        }

        $this->registerUserWithCourse($user, $this->cid);

        $this->redirect('registrations/show/'. $user->getId() .'?moocid=' . $this->cid);
    }

    private function error($msg, $url)
    {
        $this->flash['error'] = $msg;
        $this->redirect($url . '?moocid=' . $this->cid);
    }

    private function createAccount()
    {
        // TODO: check if mail adress is valid, use Stud.IP-API if possible
        $mail = Request::get('mail');
        if (\User::findByUsername($mail)) {
            throw new Exception(_('Es gibt bereits einen Nutzer mit dieser E-Mail-Adresse!'));
        }

        // add user to database
        $password = str_replace('0', 'o', substr(\base_convert(\uniqid('pass', true), 10, 36), 1, 8));

        $data = array(
            'Email'       => $mail,

            'username'    => $mail,
            'Password'    => UserManagement::getPwdHasher()->HashPassword($password),

            'Vorname'     => Request::get('vorname'),
            'Nachname'    => Request::get('nachname'),

            'perms'       => 'autor',
            'auth_plugin' => 'standard'
        );

        $user = User::create($data);

        $userinfo = new Userinfo($user->getId());
        $userinfo->store();

        $this->sendMail($this->course, $mail, $password);

        $_SESSION['mooc']['register'] = array(
            'username' => $mail,
            'password' => $password
        );

        return $user;
    }
    
    private function sendMail($course, $mail, $password)
    {
        // send mail with password to user
        $mail_msg = sprintf(
            _("Ihre Zugangsdaten für den MOOC-Kurs '%s':\n\n"
            . "Benutzername: %s \n"
            . "Passwort: %s \n\n"
            . "Hier kommen Sie direkt ins System:\n %s"),
            $course->name, $mail, $password, $GLOBALS['ABSOLUTE_URI_STUDIP']
        );
        StudipMail::sendMessage($mail, sprintf(_('Zugang zum MOOC-Kurs "%s"'), $course->name), $mail_msg);
    }

    private function loginUser()
    {
        $username = Request::get("username");
        $password = Request::get("password");

        if (isset($username) && isset($password)) {
            $result = StudipAuthAbstract::CheckAuthentication($username, $password);
        }

        if (!isset($result) || $result['uid'] === false) {
            return false;
        }

        $user = User::findByUsername($username);

        if (!$user) {
            return false;
        }

        $this->startSession($user);

        return $user;
    }

    private function startSession($user)
    {
        $GLOBALS['auth'] = new Seminar_Auth();
        $GLOBALS['auth']->auth = array(
            'uid'   => $user->user_id,
            'uname' => $user->username,
            'perm'  => $user->perms,
            "auth_plugin" => $user->auth_plugin,
        );

        $GLOBALS['user'] = new Seminar_User($user);

        $GLOBALS['perm'] = new Seminar_Perm();
        $GLOBALS['MAIL_VALIDATE_BOX'] = false;
    }

    private function registerUserWithCourse($user, $course)
    {
        $new = new CourseMember(array($course, $user->id));
        if ($new->isNew()) {
            $new->status = 'autor';
            $new->admission_studiengang_id = 'all';
            $new->label = '';
            $new->store();
        }
    }
}