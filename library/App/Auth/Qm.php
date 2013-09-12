<?php
/**
 * Der Adapter für das Authorisieren der User, der auf die Bedürfnisse der Uni-Hildesheim
 * zugeschnitten ist.
 * 
 * @category   App
 * @package    App_Auth
 * @subpackage App_Auth_Adapter
 * @author     kastners
 */
class App_Auth_Qm implements App_Auth_Adapter_Interface
{
    /**
     * @var array Userdaten (username und password)
     */
    var $identity;
    var $imap_server;
    
    /**
     * Username und Passwort für die Authentifizierung festlegen
     * 
     * @param $username Benutzername
     * @param $password Passwort
     * @param $imap_server Imap Server, auf dem der User überprüft werden soll 
     * @return void
     */
    public function __construct($username, $password, $imap_server)
    {
        $this->identity = array('username' => $username, 'password' => $password);
        $this->imap_server = $imap_server;
    }
    
    /**
     * Überprüft die eingegebenen Userdaten
     * @return App_Auth_Result
     * @see Auth/Adapter/App_Auth_Adapter_Interface#authenticate()
     */
    public function authenticate()
    {
        //TODO: weitere daten (vor allem email und rollen) in identity speichern
        //überprüfung über den imap server
        //ports: wenn SSL -> 993; wenn nicht SSL -> 143
        $accountData = array (
            'host' => $this->imap_server,
            'user' => $this->identity['username'],
            'password' => $this->identity['password']
        );
        try 
        {
            //wenn das herstellen der verbindung klappt => rückgabe eines successobjekts
            $auth = new Zend_Mail_Storage_Imap($accountData);
            return new App_Auth_Result(App_Auth_Result::SUCCESS, $this->identity);
        }
        catch (Exception $e)
        {
        }
        
        $userModel = new Default_Model_Users();
        $user_id = $userModel->checkCredentials($this->identity['username'], $this->identity['password']);
        if($user_id)
        {
            $this->identity['user_id'] = $user_id;
            $this->identity['roles'] = $userModel->getRoles($user_id);
            $this->identity['user_type'] = 'qm';
            unset($this->identity['password']);
            return new App_Auth_Result(App_Auth_Result::SUCCESS, $this->identity);
        }
        return new App_Auth_Result(App_Auth_Result::FAILURE, $this->identity);
    }
    
}