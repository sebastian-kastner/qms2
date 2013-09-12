<?php
/**
 * Controller für die Einstellungen für die Prozesse (Prozessattribute, Prozesstypen, etc..)
 * @author kastners
 *
 */
class Admin_ProcessController extends Zend_Controller_Action
{   
    /**
     * Index Action
     * @return void
     */
    public function indexAction()
    {
        
    }
    
    /**
     * Types Action. Zeigt die existierenden Prozesstypen an
     * @return void
     */
    public function typesAction()
    {
        $request = $this->getRequest();
        $model = new Process_Model_ProcessTypes();
        
        if(is_numeric($request->getParam('moveUp')))
        {
            $model->moveUp($request->getParam('moveUp'));
        }
        if(is_numeric($request->getParam('moveDown')))
        {
            $model->moveDown($request->getParam('moveDown'));
        }
        $this->view->processTypes = $model->fetchAll(null, 'position')->toArray();
    }
    
    /**
     * Action zum Bearbeiten von Prozesstypen
     * @return void
     */
    public function typeeditAction()
    {
        $settings = array(
                        'model' => 'Process_Model_ProcessTypes',
                        'id' => 'process_type_id',
                        'redirect' => '/admin/process/types',
                        'head' => 'Prozesstyp bearbeiten'
                    );
        $this->_forward('process', 'process', 'default', $settings);
    }
    
    /**
     * Hinzufügen neuer Prozesstypen
     * @return void
     */
    public function typeaddAction()
    {
        $settings = array(
                        'model' => 'Process_Model_ProcessTypes',
                        'id' => 'process_type_id',
                        'redirect' => '/admin/process/types',
                        'head' => 'Prozesstyp hinzufügen'
                    );
        $this->_forward('process', 'process', 'default', $settings);
    }
    
    /**
     * Prozesstypen löschen
     * @return void
     */
    public function typedelAction()
    {
        $model = new Process_Model_ProcessTypes();
        
        $model->delete($this->getRequest()->getParam('process_type_id'));
        $this->_forward('redirect', 'index', 'default', $value->getRedirectOptions('/admin/process/types'));
    }
    
    /**
     * Zeigt die existierenden Attribut Typen an
     * @return void
     */
    public function attributetypesAction()
    {
        $request = $this->getRequest();
        
        $model = new Process_Model_AttributeTypes();
        
        if(is_numeric($request->getParam('moveUp')))
        {
            $model->moveUp($request->getParam('moveUp'));
        }
        if(is_numeric($request->getParam('moveDown')))
        {
            $model->moveDown($request->getParam('moveDown'));
        }
        $this->view->attribute_types = $model->fetchAll()->toArray();
    }
    
    /**
     * Hinzufügen neuer Attributtypen
     * @return void
     */
    public function attributetypeaddAction()
    {
        $settings = array(
                        'model' => 'Process_Model_AttributeTypes',
                        'id' => 'process_attribute_type_id',
                        'redirect' => '/admin/process/attributeTypes',
                        'head' => 'Attributtyp hinzufügen'
                    );
        $this->_forward('process', 'process', 'default', $settings);
    }
    
    /**
     * Attributtypen bearbeiten
     * @return void
     */
    public function attributetypeeditAction()
    {
        $settings = array(
                        'model' => 'Process_Model_AttributeTypes',
                        'id' => 'process_attribute_type_id',
                        'redirect' => '/admin/process/attributeTypes',
                        'head' => 'Attributtyp bearbeiten'
                    );
        $this->_forward('process', 'process', 'default', $settings);
    }
    
    /**
     * Löscht den Attributtyp mit der im GET Parameter process_attribute_type_id gespeicherten ID
     * @return void
     */
    public function attributetypedelAction()
    {
        $model = new Process_Model_AttributeTypes();    
        $model->delete($this->getRequest()->getParam('process_attribute_type_id'));
        $redirect = array('msg' => 'Der Datensatz wurde erfolgreich gelöscht!',
                          'head' => 'Attribut Typ löschen',
                          'redirect' => '/admin/process/attributeTypes');
        $this->_forward('redirect', 'index', 'default', array('redirectOptions' => $redirect));
    }
    
    /**
     * Übersicht über die Attribute
     * @return void
     */
    public function attributesAction()
    {
        $request = $this->getRequest();
        if(is_numeric($request->getParam('attributeUp')))
        {
            $model = new Process_Model_ProcessAttributes();
            $model->moveUp($request->getParam('attributeUp'));
        }
        if(is_numeric($request->getParam('attributeDown')))
        {
            $model = new Process_Model_ProcessAttributes();
            $model->moveDown($request->getParam('attributeDown'));
        }
        
        $model = new Process_Model_ProcessAttributes();
        $this->view->attributes = $model->fetchGroupedByTypes();
    }
    
    /**
     * Attribut bearbeiten
     * @return void
     */
    public function attributeeditAction()
    {
        $settings = array(
                        'model' => 'Process_Model_ProcessAttributes',
                        'id' => 'process_attribute_id',
                        'redirect' => '/admin/process/attributes',
                        'head' => 'Attribut bearbeiten'
                    );
        $this->_forward('process', 'process', 'default', $settings);
    }
    
    /**
     * Neues Attribut anlegen
     * @return void
     */
    public function attributeaddAction()
    {
        $settings = array(
                        'model' => 'Process_Model_ProcessAttributes',
                        'id' => 'process_attribute_id',
                        'redirect' => '/admin/process/attributes',
                        'head' => 'Attribut erstellen'
                    );
        $this->_forward('process', 'process', 'default', $settings);
    }
    
    /**
     * Attribut löschen
     * @return void
     */
    public function attributedelAction()
    {
        $model = new Process_Model_ProcessAttributes();    
        $model->delete($this->getRequest()->getParam('process_attribute_id'));
        $redirect = array('msg' => 'Das Attribut wurde erfolgreich gelöscht!',
                          'head' => 'Attribut löschen',
                          'redirect' => '/admin/process/attributes');
        $this->_forward('redirect', 'index', 'default', array('redirectOptions' => $redirect));
    }
}