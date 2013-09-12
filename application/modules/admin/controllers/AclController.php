<?php
class Admin_AclController extends Zend_Controller_Action
{   
    public function indexAction()
    {
        
    }
    
    //ROLLEN
    /**
     * Übersicht über die Rollen
     * @return unknown_type
     */
    public function rolesAction()
    {
        $activation = new Zend_Session_Namespace('role_activation');
        $show_deactive = (isset($activation->show) && is_bool($activation->show)) 
                         ? ($activation->show) : false;
        
        $model = new Default_Model_Roles();
        $roles = $model->fetchAll($show_deactive);
        
        $this->view->paginator = $model->getPaginator();
        $this->view->roles = $roles->toArray();
        $this->view->show_deactive = $show_deactive;
    }
    
    /**
     * Ändert die Anzeige der nicht aktivierten Datensätze, speichert den neuen Status in der Session
     * und leitet anschließend auf die Rollenübersicht (rolesAction()) weiter
     * @return void
     */
    public function changeactivationAction()
    {
        $activation = new Zend_Session_Namespace('role_activation');
        $current = (isset($activation->show) && is_bool($activation->show)) ? ($activation->show) : false;
        $new = ($current) ? false : true;
        $activation->show = $new;

        $this->_redirect("/admin/acl/roles");
    }
    
    /**
     * Rolle hinzufügen
     */
    public function roleaddAction()
    {
        $settings = array(
                        'model' => 'Default_Model_Roles',
                        'id' => 'role_id',
                        'redirect' => '/admin/acl/roles',
                        'head' => 'Rolle hinzufügen'
                    );
        $this->_forward('process', 'process', 'default', $settings);
    }
    
    /**
     * Rolle bearbeiten
     */
    public function roleseditAction()
    {
        $request = $this->getRequest();
        
        $settings = array(
                        'model' => 'Default_Model_Roles',
                        'id' => 'role_id',
                        'redirect' => '/admin/acl/roles',
                        'head' => 'Rolle bearbeiten'
                    );
        $this->_forward('process', 'process', 'default', $settings);
    }
    
    /**
     * Rolle löschen
     * @return void
     */
    public function rolesdelAction()
    {
        $model = new Default_Model_Roles();
        $model->delete($this->getRequest()->getParam('role_id'));
        $settings = array('redirect' => '/admin/acl/roles',
                          'head' => 'Rolle löschen',
                          'msg' => 'Die Rolle wurde erfolgreich gelöscht!');
        $this->_forward('redirect', 'index', 'default', array('redirectOptions' => $settings));
    }
    
    /**
     * Aktivierungsstatus einer Rolle ändern
     * @return void
     */
    public function rolesactivationAction()
    {
        $model = new Default_Model_Roles();
        $request = $this->getRequest();
        $model->changeActiveState($request->getParam('role_id'));
        
        $this->_redirect($_SERVER['HTTP_REFERER']);
    }
    
    /**
     * Rechte für eine bestimmte Benutzergruppe bearbeiten
     * @return void
     */
    public function rightseditAction()
    {
        $role_id = $this->getRequest()->getParam('role_id');
        $user_id = $this->getRequest()->getParam('user_id');
        
        if(!is_numeric($role_id) && !is_numeric($user_id))
        {
            throw new Zend_Controller_Exception('Es wurde weder eine gültige Rollen ID noch eine gültige User ID übergeben!');
        }
        
        if($role_id != null)
        {
            $rightType = 'role';
            $roles = new Default_Model_Roles();
            $name = $roles->fetch($role_id)->role_name;
            $type = "Rolle";
            $model = new Default_Model_RightsRole();
            $id = $role_id;
        }
        else
        {
            $rightType = 'user';
            $roles = new Default_Model_Users();
            $name = $roles->fetch($user_id)->username;
            $type = "Benutzer";
            $model = new Default_Model_RightsUser();
            $id = $user_id;
        }
        $this->view->name = $name;
        $this->view->type = $type;
        
        if(!$this->getRequest()->isPost())
        {
            $value = $model->getValue();
            $this->view->form = $value->getForm();
        }
        else
        {
            $model->saveRights($id, $this->getRequest()->getPost());
            //acl objekt aus cache löschen, damit es mit den änderungen neu erstellt wird
            $cache = App_Controller_Plugin_Acl::getCache();
            $cache->remove('acl');
            if($role_id != null)
            {
                $redirect = array(
                                'head' => 'Rollenrechte bearbeiten',
                                'msg' => 'Sie haben die Zugriffsrechte der Rolle erfolgreich bearbeitet!',
                                //'redirect' => '/admin/acl/roles'
                            );
            }
            else
            {
                $redirect = array(
                                'head' => 'Benutzerrechte bearbeiten',
                                'msg' => 'Sie haben die Zugriffsrechte erfolgreich bearbeitet!'
                                //'redirect' => '/admin/user'
                            );
            }
            $this->_forward('redirect', 'index', 'default', array('redirectOptions' => $redirect));
        }
    }
    
    /**
     * Resourcen Übersicht
     * @return void
     */
    public function resourcesAction()
    {
        $model = new Default_Model_Resources();
        $resources = $model->fetchAll()->toArray();
        $this->view->resources = $resources;
    }
    
    /**
     * Resource bearbeiten
     * @return void
     */
    public function resourceeditAction()
    {
        $settings = array(
                        'model' => 'Default_Model_Resources',
                        'id' => 'resource_id',
                        'redirect' => '/admin/acl/resources',
                        'head' => 'Resource bearbeiten'
                    );
        $this->_forward('process', 'process', 'default', $settings);
    }
}