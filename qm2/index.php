<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
    
// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));


// Define application environment
if($_SERVER['HTTP_HOST'] != 'localhost')
{
    define('APPLICATION_ENV', 'production');
}

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

//PUBLIC_DIR: ort des "public" ordners angeben
//define('PUBLIC_DIR', (APPLICATION_ENV == 'production') ? '/qms2' : '/QMS2/qms2');

/** Zend_Application */
require_once 'Zend/Application.php';  

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV, 
    APPLICATION_PATH . '/configs/application.ini'
);

$application->getAutoloader()->registerNamespace('ZendX')
                             ->registerNamespace('App');
                             
//logger instanziieren
if(APPLICATION_ENV != 'production')
{
    $logger = new Zend_Log();
    $writer = new Zend_Log_Writer_Firebug();
    $logger->addWriter($writer);
    Zend_Registry::set('logger', $logger);
    
    function fbLog($message, $label=null)
    {
        if($label != null) 
        {
            $message = array($label, $message);
        }
        Zend_Registry::get('logger')->debug($message);
    }
}
else
{
    function fbLog($message, $label=null)
    {
        return;
    }    
}
$application->bootstrap()
            ->run();
