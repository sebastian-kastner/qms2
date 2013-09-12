<?php
class Process_Bootstrap extends Zend_Application_Module_Bootstrap
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
            'namespace' => 'Process',
            'basePath'  => dirname(__FILE__),
        ));
        
        return $autoloader;
    }
    
    protected function _initRoutes()
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();
       
//         $route = new Zend_Controller_Router_Route_Regex(
//           'process/(\d+)',
//           array(
//               'controller' => 'index',
//               'action' => 'show',
//               'module' => 'process'
//           ),
//           array(
//               1 => 'process_id'
//           ),
//           'process/%s'
//         );
//         $router->addRoute('processShow', $route);
        
        $route = new Zend_Controller_Router_Route_Regex(
          'process/(\d+)(/[\wäöüÄÖÜß]+/(\d+))?',
          array(
              'controller' => 'index',
              'action' => 'show',
              'module' => 'process'
          ),
          array(
              1 => 'process_id',
              3 => 'attribute_type_id'
          ),
          'process/%s'
        );
        $router->addRoute('processShow', $route); 
    }
}