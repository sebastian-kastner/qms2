<?php
/**
 * Controller für AJAX Requests im Default Module
 * @author kastners
 *
 */
class AsyncController extends Zend_Controller_Action
{
    /**
     * rendering und layout in abschalten
     */
    public function init()
    {
        //festlegen, dass nichts gerendert wird
        $this->_helper->viewRenderer->setNoRender();
        //layout disablen
        $this->_helper->getHelper('layout')->disableLayout();
    }
    
    /**
     * Sucht zu einem Suchstring passende Prozesse
     */
    public function rolesearchAction()
    {
        $search = $this->getRequest()->getParam('term');
        $model = new Default_Model_Roles();
        $result = $model->roleSearch($search);
        $output = array();
        foreach($result AS $process)
        {
            $output[] = array('id' => $process['role_id'], 'label' => $process['role_name']);
        }
        echo Zend_Json::encode($output);
    }
}