<?php
/**
 * Controller für AJAX Requests
 * @author kastners
 *
 */
class Process_AsyncController extends Zend_Controller_Action
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
     * 
     */
    public function preDispatch()
    {
        
    }
    
    /**
     * Liest zu einem Prozess die Unterprozesse aus
     */
    public function getprocesschildsAction()
    {
        $process_id = $this->getRequest()->getParam('process_id');
        $model = new Process_Model_Process();
        $childs = $model->getDirectDescendants($process_id, array(App_Db_Tree::WHERE => App_Db_Tree::NODE_ALIAS.'.is_active = 1'));
        
        echo Zend_Json::encode($childs);
    }
    
    /**
     * Sucht zu einem Suchstring passende Prozesse
     */
    public function processsearchAction()
    {
        $search = $this->getRequest()->getParam('term');
        $model = new Process_Model_Process();
        $result = $model->processSearch($search);
        $hits = array();
        foreach($result AS $process)
        {
            $hits[] = array('id' => $process['process_id'], 'label' => $process['name']);
        }
        /* foreach($result AS $process)
        {
            echo $process['name'].'|'.$process['process_id']."\n";
        } */
        echo Zend_Json::encode(array_values($hits));
    }
    
    /**
     * Liest genau ein Prozessattribut zu einem Prozess
     */
    public function getprocessattributeAction()
    {
        $request = $this->getRequest();
        $process_id = $request->getParam('process_id');
        $attribute_id = $request->getParam('attribute_id');
        
        $model = new Process_Model_Process();
        $attribute = $model->getProcessAttribute($process_id, $attribute_id);
        
        echo Zend_Json::encode($attribute);    
    }
    
    /**
     * Speichert ein Prozessattribut
     */
    public function saveprocessattributeAction()
    {
        $request = $this->getRequest();
        $model = new Process_Model_ProcessHasAttribute();
        
        $pha = $model->getValue();
        $pha->populate($request->getPost());
        $pha->save();
    }
}