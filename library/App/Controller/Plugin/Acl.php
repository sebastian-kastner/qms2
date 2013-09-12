<?php
/**
 * Fungiert als Controller Plugin und wird vor dem Dispatch Prozess ausgeführt
 * Zunächst wird überprüft, ob der User eingeloggt ist. Wenn nicht, dann werden ihm 
 * Gastrechte zugewiesen. Ist er eingeloggt werden seine Rechte anhand seiner zugeteilten
 * Gruppen zugeteilt
 /**
 * @category   App
 * @package    App_Controller
 * @subpackage Plugins
 * @author     kastners
 *
 */
class App_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{
    /**
     * Cache Objekt
     * @var Zend_Cache_Core
     */
    protected static $_cache = null;

    /**
     * Teilt dem User zum Beginn der Programmausführung seine Benutzerrechte zu. Falls der Benutzer nicht
     * eingeloggt ist oder dem Benutzer keine besonderen Rechte zugeordnet sind werden dem Benutzer die
     * Gastrechte zugeschrieben
     * @see library/Zend/Controller/Plugin/Zend_Controller_Plugin_Abstract#dispatchLoopStartup($request)
     */
    public function dispatchLoopStartup()
    {
        $user = App_Auth::getInstance();

        $acl = $this->_getAcl();
        $view = Zend_Layout::getMvcInstance()->getView();
        $hasIdentity = $user->hasIdentity();
        $view->loggedIn = $hasIdentity;
        if($hasIdentity)
        {
            //user ist eingeloggt. falls für den benutzer noch kein eintrag im acl objekt vorhanden ist wird er hier erstellt
            $identity = $user->getIdentity();
            if(!$acl->hasUser($identity['user_id']))
            {
                $acl->createUser($identity['user_id'], $identity['roles']);
                //$this->getCache()->remove('acl');
                $this->getCache()->save($acl, 'acl');
            }
        }
    }

    /*

    Verfügbare funktionen des soap clients

    array(9) {
    [0] => string(44) "string process(string $system, string $body)"
    [1] => string(44) "string process(string $system, string $body)"
    [2] => string(46) "string getData(string $strName, string $strID)"
    [3] => string(58) "string getDataByParams(string $strName, string $xmlParams)"
    [4] => string(58) "string getDataByParams(string $strName, string $xmlParams)"
    [5] => string(36) "string getDataXML(string $xmlParams)"
    [6] => string(46) "string getDataXMLByAuthUser(string $xmlParams)"
    [7] => string(38) "string getDataSearch(string $elParams)"
    [8] => string(86) "string SOAPDataImporter(string $strXMLStructure, string $strUser, string $strPassword)"
    }
    */

    /**
     * Rechte des Benutzers bestimmen
     * @return App_Acl
     */
    protected function _getAcl()
    {
        //$client = new SoapClient("https://lsf-test.uni-hildesheim.de/qisserver/services/dbinterface?wsdl");
        //$result = $client->getData('kastners');//array('strName' => null, 'strID' => 147));
        //Zend_Debug::dump($result);

        //url für soap anfragen:
        //https://lsf-test.uni-hildesheim.de/qisserver/services/dbinterface?wsdl
        //$soap = new Zend_Soap_Client("https://lsf-test.uni-hildesheim.de/qisserver/services/dbinterface?wsdl");

        //$client = new SoapClient("http://footballpool.dataaccess.eu/data/info.wso?wsdl");
        /* $soap = new Zend_Soap_Client("http://footballpool.dataaccess.eu/data/info.wso?wsdl");
        try {
        $result = $soap->TopGoalScorers(array('iTopN' => 5));
        }
        catch (Exception $e)
        {
        echo $e->getMessage();
        Zend_Debug::dump($soap->getLastRequest());
        } */

        /*
         $client = new SoapClient("http://footballpool.dataaccess.eu/data/info.wso?wsdl");
         $result = $client->TopGoalScorers(array('iTopN' => 5));
         Zend_Debug::dump($result->TopGoalScorersResult->tTopGoalScorer);
         */
        $cache = self::getCache();
        $acl = $cache->load('acl');
        if(!$acl)
        {
            $acl = new App_Acl();
            $acl->createResources();
            $acl->createGuestRole();
            //cachen
            $cache->save($acl, 'acl');
        }
        else
        {
        }
        //in registry schreiben
        Zend_Registry::set('acl', $acl);
        return $acl;
    }

    /**
     * Gibt das Cache Objekt zurück oder erstellt eines, falls noch keines vorhanden ist
     * @return Zend_Cache_Core
     */
    public static function getCache()
    {
        if(self::$_cache == null)
        {
            $frontendOptions = array(
                                'life_time' => 3600,
                                'automatic_serialization' => true
                               );
            $backendOptions = array('cacheDir' => './tmp/');
            self::$_cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        }
        return self::$_cache;
    }
}