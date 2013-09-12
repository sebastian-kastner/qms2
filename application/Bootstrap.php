<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{    
    protected $view;
    protected $controller;

    protected function _initSession()
    {
        Zend_Session::start();
    }
    
    public function _initTheView()
    {
        $this->bootstrap('view');
        $this->view = $this->getResource('view');
        $this->view->addBasePath('application/views/', 'Default');

        $this->view->addHelperPath('App/View/Helper', 'App_View_Helper');
        //View Helper Path für die JQuery Library hinzufügen
        $this->view->addHelperPath('ZendX/JQuery/View/Helper', 'ZendX_JQuery_View_Helper');
       
        $doctypeHelper = new Zend_View_Helper_Doctype(); 
        $doctypeHelper->doctype('XHTML1_STRICT');
        //titel setzen
        $this->view->headTitle('Elektronisches QMS');
        $this->view->headTitle()->setSeparator(' - ');
    }
    
    public function _initPlugins()
    {  
        //Plugin für die Breadcrumbs registrieren
        $controller = Zend_Controller_Front::getInstance();
        $controller->registerPlugin(new App_Controller_Plugin_Acl());
        $controller->registerPlugin(new App_Controller_Plugin_Breadcrumbs());
        $this->controller = $controller;
        
        //$this->view->addHelperPath('App/View/Helper');
    }
    
    public function _initDatabase()
    {
        //konfiguration abhängig von der umgebung laden
        $db_conf = new Zend_Config_Ini(APPLICATION_PATH.'/configs/database.ini', APPLICATION_ENV);
        $db = Zend_Db::factory('Pdo_Mysql', $db_conf);
        
        Zend_Db_Table_Abstract::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);
        
        //profiler verwenden
        if(APPLICATION_ENV != 'production')
        {
            $profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
            $profiler->setEnabled(true);
            $db->setProfiler($profiler);       
        }
    }
    /*
    protected function _initRegisterNamespace()
    {
        $this->getApplication()->getAutoloader()->registerNamespace('ZendX')
                                                ->registerNamespace('App');
        
    } */
    
    protected function _initAutoload()
    {       
        $autoloader = new App_Application_Module_Autoloader(array(
            'namespace' => 'Default',
            'basePath'  => dirname(__FILE__),
        ));
        
        return $autoloader;
    }
    
    protected function _initPaginationControl()
    {
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('paginationControl.phtml');
        Zend_Paginator::setDefaultScrollingStyle('Sliding');
    }
}

