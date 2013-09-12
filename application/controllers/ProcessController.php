<?php
/**
 * Controller, der jegliche Standard INSERT und UPDATE Operationen ausführt
 * Bei Bedarf wird auf diesen Controller referenziert 
 * (Weiterleitung aus anderen Controllern mittels $this->_forward())
 * @author kastners
 *
 */
class ProcessController extends Zend_Controller_Action
{    
    /**
     * Führt standardmäßige UPDATE und INSERT Operationen aus. Wird über _forward aus anderen Controllern aufgerufen
     * Das Formular muss mit POST abgeschickt werden
     * Mögliche Parameter sind: 
     *      model     ->      Name der Model Klasse (muss angegeben werden!)
     *      id        ->      Name des Parameters, in dem die ID gespeichert wird. Wenn nicht angegeben wird "id" angenommen (optional)
     *      redirect  ->      URI der Seite, auf die verwiesen werden soll
     *      form      ->      (String) Kann gesetzt werden, wenn nicht das Standardformular der Value Klasse verwendet werden soll
     * @return void
     */
    public function processAction()
    {
        $request = $this->getRequest();
        
        $modelParam = $request->getParam('model');
        $model = $this->getModel($modelParam);
        
        $idField = $request->getParam('id', 'id');
        $id = $request->getParam($idField);
        $redirectTo = $request->getParam('redirect');
        $head = $request->getParam('head');

        if(!empty($id))
        {
            $value = $model->fetch($id);
        }
        else
        {
            $value = $model->getValue();
        }
        
        $form_class = $request->getParam('form', null);
        if($form_class != null)
        {
            $value->setFormClass($form_class);
        }
        
        //formular wurde abgeschickt
        if($request->isPost())
        {
            $value->populate($request->getPost());
            $success = $value->save();
            if($success)
            {
                $redirect = $value->getRedirectOptions($redirectTo);
                $this->_forward('redirect', 'index', 'default', $redirect);
                return;
            }
            else
            {
                $form = $value->getForm($request->getPost());
            }
        }
        else
        {
            $form = $value->getForm();
            //$form->populate($value);
        }
        
        $form->setTitle($head);
        
        $this->view->head = $head;
        $this->view->form = $form;
    }
    
    /**
     * Löscht den Datensatz mit der übergebenen ID
     * @return void
     */
    /* public function deleteAction()
    {
        $request = $this->getRequest();
        
        $modelParam = $request->getParam('model');
        $model = $this->getModel($modelParam);
        
        $idField = $request->getParam('id', 'id');
        $id = $request->getParam($idField);
        $redirectTo = $request->getParam('redirect');
        $head = $request->getParam('head');
        
        $model->delete($id);
        
    } */
    
    /**
     * Unterscheidet, ob es sich bei dem übergebenen Parameter um einen String oder um ein Model Objekt handelt.
     * Wenn es sich um eine Model Objekt handelt, dann wird dieses direkt zurückgegeben. Ansonsten wird der übergebene
     * String als der Name der Model Klasse verstanden und ein Objekt davon erstellt
     * @param string|App_Model_Gateway_Abstract $model
     * @return App_Model_Gateway_Abstract
     */
    protected function getModel($model)
    {
        if($model instanceof App_Model_Gateway_Abstract)
        {
            return $model;
        }
        
        try 
        {
            return new $model();
        }
        catch (Exception $e)
        {
            throw new App_Exception("Für die processAction muss eine gültige Model Klasse übergeben werden!");
        }
    }
}