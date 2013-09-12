<?php
class Admin_UserController extends Zend_Controller_Action
{
    /**
     * Benutzerübersicht als Controller Index
     * @return void
     */
    public function indexAction()
    {
        $model = new Default_Model_Users();
        $users = $model->fetchAll()->toArray();

        $this->view->users = $users;
        $this->view->paginator = $model->getPaginator();
    }

    /**
     * Benutzer hinzufügen
     * @return void
     */
    public function addAction()
    {
        $this->view->headLink()->appendStylesheet(
        $this->view->baseUrl()."/css/jquery-ui-1.8.11.custom.css",
            'screen'
            );
            $this->view->headScript()->appendFile($this->view->baseUrl()."/js/user-roles.js");
            $this->view->jQuery()->enable()->uiEnable();
            $settings = array(
                        'model' => 'Default_Model_Users',
                        'id' => 'user_id',
                        'redirect' => '/admin/user',
                        'head' => 'Benutzer hinzufügen'
                        );
                        $this->_forward('process', 'process', 'default', $settings);
    }

    /**
     * Benutzer bearbeiten
     * @return void
     */
    public function editAction()
    {
        $this->view->headLink()->appendStylesheet(
        $this->view->baseUrl()."/css/jquery-ui-1.8.11.custom.css",
            'screen'
            );
            $this->view->headScript()->appendFile($this->view->baseUrl()."/js/user-roles.js");
            $this->view->jQuery()->enable()->uiEnable();
            $settings = array(
                    'model' => 'Default_Model_Users',
                    'id' => 'user_id',
                    'redirect' => '/admin/user',
                    'head' => 'Benutzer bearbeiten'
                    );
                    $this->_forward('process', 'process', 'default', $settings);
    }

    /**
     * Rollen des Benutzers bearbeiten
     * @return void
     */
    public function rolesAction()
    {
        $request = $this->getRequest();
        if($request->getParam('user_id', null) == null)
        {
            throw new Zend_Controller_Exception("Zum Bearbeiten der Benutzerrollen ist eine Benutzer ID nötig, 
                                                 es wurde aber keine übergeben.");
        }

        $model = new Default_Model_Users();
        $user = $model->getValue($request->getParam('user_id'));
        $user->setFormClass("Admin_Model_Form_UserRoles");

        //wenn das formular noch nicht abgeschickt wurde: anzeige des formulars
        if(!$request->isPost())
        {
            $this->view->headLink()->appendStylesheet(
            $this->view->baseUrl()."/css/jquery-ui-1.8.11.custom.css",
            'screen'
            );
            $this->view->headScript()->appendFile($this->view->baseUrl()."/js/user-roles.js");
            $this->view->jQuery()->enable()->uiEnable();
            $this->view->form = $user->getForm();
            $this->view->form->setTitle('Benutzerrollen bearbeiten');
        }
        //wenn das formular abgeschickt wurde: update der rollen des benutzers und weiterleitung
        else
        {
            $user->populate($request->getParams());
            $user->saveRoles();
            $user->setLastAction('update');
            $redirect = $user->getRedirectOptions('/admin/user');
            //acl objekt aus cache löschen, damit es mit den änderungen neu erstellt wird
            $cache = App_Controller_Plugin_Acl::getCache();
            $cache->remove('acl');
            $this->_forward('redirect', 'index', 'default', $redirect);
        }
    }

    /**
     * Benutzerrechte bearbeiten
     * @return void
     */
    public function rightseditAction()
    {
        $user_id = $this->getRequest()->getParam('user_id');
        $this->_forward('rightsEdit', 'acl', 'admin', array('user_id' => $user_id));
    }
}