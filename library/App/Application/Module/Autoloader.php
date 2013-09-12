<?php

require_once 'Zend/Loader/Autoloader/Resource.php';
/**
 * @category   App
 * @package    App_Application
 * @subpackage Module
 * @author     kastners
 */

/** @see Zend_Application_Module_Autoloader */

class App_Application_Module_Autoloader extends Zend_Loader_Autoloader_Resource
{
    /**
     * Constructor
     *
     * @param  array|Zend_Config $options
     * @return void
     */
    public function __construct($options)
    {
        parent::__construct($options);
        $this->initDefaultResourceTypes();
    }

    /**
     * Initialize default resource types for module resource classes
     *
     * @return void
     */
    public function initDefaultResourceTypes()
    {
        $basePath = $this->getBasePath();
        $this->addResourceTypes(array(
            'dbtable' => array(
                'namespace' => 'Model_DbTable',
                'path'      => 'models/DbTable',
            ),
            'form'    => array(
                'namespace' => 'Model_Form',
                'path'      => 'models/forms',
            ),
            'model'   => array(
                'namespace' => 'Model',
                'path'      => 'models',
            ),
            'plugin'  => array(
                'namespace' => 'Plugin',
                'path'      => 'plugins',
            ),
            'service' => array(
                'namespace' => 'Service',
                'path'      => 'services',
            ),
            'viewhelper' => array(
                'namespace' => 'View_Helper',
                'path'      => 'views/helpers',
            ),
            'viewfilter' => array(
                'namespace' => 'View_Filter',
                'path'      => 'views/filters',
            ),
            'values' => array(
                'namespace' => 'Model_Value',
                'path' => 'models/values'
            ),
            'resultset' => array(
                'namespace' => 'Model_Resultset',
                'path' => 'models/resultsets'
            )
        ));
        $this->setDefaultResourceType('model');
    }
}