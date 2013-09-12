<?php
/**
 * Index für das Prozessmodul
 * @author kastners
 *
 */
class Process_IndexController extends Zend_Controller_Action
{
    /**
     * Model Objekt
     * @var Process_Model_Process
     */
    protected $model = null;
    
    /**
     * Erzeugt das Model und speichert das Model im Controller Objekt
     * @see Controller/Zend_Controller_Action#preDispatch()
     */
    public function preDispatch()
    {
        $this->model = new Process_Model_Process();
    }
    
    /**
     * Liest die Liste mit den Prozessen der ersten Ebene aus und übergibt diese dem View
     * @return void
     */
    public function indexAction()
    {
        $processList = $this->model->getProcessList();
        $this->view->processSearch = new ZendX_JQuery_Form_Element_AutoComplete("process-search");
        $this->view->processSearch->setJQueryParams(
                        array(
                            'source' => 'process/async/processSearch/',
                            'minLength' => 3,
                            'select' => new Zend_Json_Expr('function(event, ui){location.href = "process/" + ui.item.id;}')
                        ));
        $this->view->processList = $processList;
        $this->view->headTitle('Prozesslandschaft');            
    }
    
    /**
     * Zeigt einen einzelnen Prozess mit den dazugehörigen Details an
     * Wird bei URIs mit dem Muster "/process/[process_id]" aufgerufen. /process/16 zeigt zB den Prozess mit der ID 16 an 
     * @return void
     */
    public function showAction()
    {
        //process_id auslesen
        $process_id = $this->getRequest()->getParam('process_id');
        
        //attribute_type_id auslesen (default wert ist 4)
        $attribute_type_id = $this->getRequest()->getParam('attribute_type_id');
        //prozess objekt (value objekt) auslesen
        $process = $this->model->fetch($process_id);
        //prozessdaten in array konvertieren und an view schicken
        $this->view->process = $process->toArray();
        
        //attribut typen auslesen und an view schicken
        $attTypes = new Process_Model_AttributeTypes();
        $attribute_types = $attTypes->fetchAttributeTypes($attribute_type_id);
        $this->view->attribute_types = $attribute_types;
        
        //wenn attribute_type_id null ist wird die als aktiver attribut typ die id des attribut typs genommen, 
        //welcher an erster stelle steht, also im tab menu ganz links ist bzw in der datenbank den rang 1 hat
        if($attribute_type_id == null)
        {
            $attribute_type_id = $attribute_types[0]['process_attribute_type_id'];
        }
        
//        $this->view->navigation()->menu()->getContainer()->findBy('id', 'process')->setActive();
        $navParent = $this->view->navigation()->menu()->getContainer()->findBy('id', 'process');
        $navParent->addPage(
                        array(
                            'label'         => $this->view->process['notation'] . " " . $this->view->process['name'],
                            'module'        => 'process',
                            'controller'    => 'index',
                            'action'        => 'show',
                            'route'         => 'processShow',
                            'active'        => true,
                            'params'        => array(
                                                'id' => $this->view->process['process_id']
                                               )
                        )
        ); 
        //prozessattribute vom prozess objekt auslesen und an view schicken
        $this->view->process_attributes = $process->getProcessAttributes($attribute_type_id);
    }
    
    /**
     * Schiebt einen Prozess im Prozessbaum nach oben
     * @return void
     */
    public function processupAction()
    {
        $id = $this->getRequest()->getParam('process_id');
        if(is_numeric($id))
        {
            $this->model->moveUp($id);
        }
        App_Redirector::redirectToPrev();
    }
    
    /**
     * Schiebt den Prozess im Prozessbaum nach unten
     * @return void
     */
    public function processdownAction()
    {
        $id = $this->getRequest()->getParam('process_id');
        if(is_numeric($id))
        {
            $this->model->moveDown($id);
        }
        App_Redirector::redirectToPrev();
    }
}