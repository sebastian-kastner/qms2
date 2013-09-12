<?php
class Admin_Bootstrap extends Zend_Application_Module_Bootstrap
{
    /**
     * Start Module Autoloader
     * 
     * @access protected
     * @return Zend_Application_Module_Autoloader
     */
    protected function _initAutoload()
    {
        $autoloader = new App_Application_Module_Autoloader(array(
            'namespace' => 'Admin',
            'basePath'  => dirname(__FILE__),
        ));
        
        return $autoloader;
    }
    
}