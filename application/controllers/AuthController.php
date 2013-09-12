<?php
class AuthController extends Zend_Controller_Action
{
    public function loginAction()
    {
        $request = $this->getRequest();
        $username = $request->getPost('username');
        $password = $request->getPost('password');
        $imap = "imap.uni-hildesheim.de";
        
        $auth = App_Auth::getInstance();
        $auth_adapter = new App_Auth_Qm($username, $password, $imap);
        
        //überprüft die benutzerdaten und schreibt die benutzerdaten im erfolgsfall in die session
        //im fehlerfall wird eine exception geworfen und der programmablauf unterbrochen
        //$auth == $auth->authenticate($auth_adapter);
        $login = $auth->authenticate($auth_adapter);
        if(!$login->isValid())
        {
            $this->render('authorization-failed');
        }
        else
        {
            //benutzer rollen erstellen
            $identity = $auth->getIdentity();
        }
    }
    
    public function logoutAction()
    {
        $auth = App_Auth::getInstance();
        $auth->clearIdentity();
        
        $this->_forward('redirect', 'index');
    }
}