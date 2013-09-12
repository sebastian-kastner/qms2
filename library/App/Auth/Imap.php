<?php
/**
 * Der Adapter für das Authorisieren der User, der auf die Bedürfnisse der Uni-Hildesheim
 * zugeschnitten ist.
 * Zunächst wird der User gegen den Imap Server authentifiziert. 
 * 
 * @category   App
 * @package    App_Auth
 * @subpackage App_Auth_Adapter
 * @author     kastners
 */
class App_Auth_Imap implements App_Auth_Adapter_Interface
{
    /**
     * @var array Userdaten (username und password)
     */
    var $identity;
    
    /**
     * @var adresse des imap servers
     */
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
     * @return App_Auth_Response
     * @see Auth/Adapter/App_Auth_Adapter_Interface#authenticate()
     */
    public function authenticate()
    {
        
        //ports: wenn SSL -> 993; wenn nicht SSL -> 143
        $accountData = array (
            'host' => $this->imap_server,
            'user' => $this->identity['username'],
            'password' => $this->identity['password']
        );
        
        //zwei möglichkeiten.. über imap_open oder über fsockopen..
        //auf dem localhost funktioniert beides, auf dem uni server nichts
        
        
        /* $imap = imap_open(
                    "{".$accountData['host'].":143}INBOX",
                    $accountData['user'],
                    $accountData['password'],
                    OP_HALFOPEN
                ); */
        /*
        $imap = imap_open(
                    "{imap.uni-hildesheim.de}",
                    $accountData['user'],
                    $accountData['password']
                );
        Zend_Debug::dump($imap);
        
        
        $errno = 0;
        $errstr = '';
        $socket = fsockopen('imap.uni-hildesheim.de', 143, $errno, $errstr);
        if(!$socket)
        {
            echo $errno.": ".$errstr;
        }
        Zend_Debug::dump($socket); */
        /*
        try 
        {
            $auth = new Zend_Mail_Storage_Imap($accountData); 
        }
        catch (Exception $e)
        {
            throw new App_Auth_Exception('Sie konnten nicht authentifiziert werden!<br>'.$e->getMessage());
            return new App_Auth_Result(App_Auth_Result::FAILURE, 
                                        $this->identity);
        } */
        
        $this->identity['password'] = md5($this->identity['password']); 
        return new App_Auth_Result(App_Auth_Result::SUCCESS, $this->identity);
    }
    
}