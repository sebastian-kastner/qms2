<?php
/**
 * Controller für die Prozesssuche
 * @author kastners
 *
 */
class Process_SearchController extends Zend_Controller_Action
{
    protected $model;
    protected $request;
    
    public function preDispatch()
    {
        $this->model = new Process_Model_Process();
        $this->request = $this->getRequest();
    }
    /**
     * Prozesssuche
     */
    public function indexAction()
    {
       $process_name = $this->request->getParam('name');
       $search_result = $this->model->processSearch($process_name);
       
       $this->view->search_term = $process_name;
       
       //keine ergebnisse gefunden => No Results Seite anzeigen
       if(count($search_result) == 0)
       {
           //TODO: irgendwie wird das view script nid gefunden..
           //$this->view->render('no_results');
           //return;
       }
       $this->view->processes = $search_result;
    }
}