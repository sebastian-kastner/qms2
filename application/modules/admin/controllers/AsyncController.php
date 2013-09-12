<?php
/**
 * Controller für AJAX Requests
 * @author kastners
 *
 */
class Admin_AsyncController extends Zend_Controller_Action
{
    /**
     * Wird wie ein Konstruktor ganz am Anfang aufgerufen. Kümmert sich darum, dass der Controller
     * für das Rendern der Seite verantwortlich ist und kein Layout oä verwendet wird
     */
    public function init()
    {
        //festlegen, dass nichts gerendert wird
        $this->_helper->viewRenderer->setNoRender();
        //layout disablen
        $this->_helper->getHelper('layout')->disableLayout();
    }   
    
    /**
     * Liest zu einem Prozess die Unterprozesse aus
     */
    public function rolesearchAction()
    {
        $search = $this->getRequest()->getParam('term');
        $model = new Default_Model_Roles();
        $result = $model->roleSearch($search);
        $hits = array();
        foreach($result AS $role)
        {
            $hits[] = array('id' => $role['role_id'], 'label' => $role['role_name']);
        }
        echo Zend_Json::encode(array_values($hits));
    }
    
    public function getuserrolesAction() 
    {
        $role_ids = $this->getRequest()->getParam('role_ids');
        $role_ids = trim($role_ids);
        
        $hits = array();
        
        if($role_ids != "")
        {
            $role_ids = str_replace(" ", ", ", $role_ids);
            $model = new Default_Model_Roles();
            $result = $model->getRolesByIds($role_ids);
            
            foreach($result AS $role)
            {
                $hits[] = array('id' => $role['role_id'], 'label' => $role['role_name']);
            }
        }
        echo Zend_Json::encode(array_values($hits));
    }
    
    /**
     * Ändert den Aktivierungsstatus einer Rolle
     */
    public function roleactivationAction()
    {
        $role_id = $this->getRequest()->getParam('id', null);
        if($role_id == null)
        {
            echo 0;
            return;
        }
        $model = new Default_Model_Roles();
        try {
            $model->changeActiveState($role_id);
        }
        catch (Exception $e)
        {
            echo 0;
            return;
        }
        echo 1;
    }
}