<?php
/**
 * Value Klasse für die Prozesse
 * @uses App_Model_Value_Abstract
 * @author kastners
 * @package Admin
 * @subpackage Value
 *
 */
class Process_Model_Value_Process extends App_Model_Value_TreeAbstract
{    
    protected $_tableClass = 'Process_Model_DbTable_Process';
    protected $_entityName = 'Prozess';
    protected $_columns = array('notation', 'name');
    
    /**
     * Liest die Daten zu einem Prozess aus und gibt diese in einem Array zurück
     * Wenn der Bearbeitungsmodus aktiviert ist werden alle attribute ausgelesen, egal ob
     * das jeweilige attribut für den prozess bestimmt wurde oder nicht
     * @param int $process_id
     * @return array
     */
    public function getProcessAttributes($attribute_type_id = null)
    {
        if(!$this->checkPrimary())
        {
            throw new App_Model_Exception('Für den Prozess können keine Attribute ausgelesen werden, 
                                          da für den Prozess keine ID im Value Objekt gespeichert ist!');
        }
        
        $db = $this->getTable()->getAdapter();
        $select = $db->select();
        $id = $db->quote($this->_data[$this->getPrimary()], Zend_Db::INT_TYPE);
        
        $select
               ->from(
                  array('pa' => 'process_attributes'),
                  array('process_attribute_id', 'name', 'form_size', 'form_type')
                 )
               ->joinLeft(
                  array('pha' => 'process_has_attribute'),
                  '(pa.process_attribute_id = pha.process_attribute_id
                    AND
                    pha.process_id = '.$id.')',
                  array('attribute_value', 'pha_id')
                 )
               ->where('pa.is_active = 1')
               ->order('pa.position');

        $texttype = App_Process_Attribute_Abstract::TEXTTYPE;
        if(App_EditMode::isEditMode())
        {
            $select->where('pha.process_id = '.$id.' OR pha.process_id IS NULL');
        }
        else
        {
            $select->where("pha.process_id = ".$id." 
                            OR (
                              pha.process_id IS NULL 
                              AND 
                              pa.form_type != '".$texttype."'
                            )");
        }
        
        if($attribute_type_id != null)
        {
            $select->where('process_attribute_type_id = '.$db->quote($attribute_type_id, Zend_Db::INT_TYPE));
        }
        
        $res = $select->query();
        $attributes = new App_Process_Broker();
        
        while($row = $res->fetch())
        {
            if($row['form_type'] == $texttype)
            {
                $att = new App_Process_Attribute_Text($row);
            }
            else
            {
                $att = new App_Process_Attribute_Method($row, $this);
            }
            $attributes->addAttribute($att);
        }
        
        return $attributes->toArray();
    }
    
    /**
     * Prozessschnittstellen auslesen
     * @return array
     */
    public function getProcessInterrelations()
    {        
        if(!$this->checkPrimary())
        {
            throw new App_Model_Exception('Für den Prozess können Relationen ausgelesen werden, da für den 
                                           Prozess keine ID im Value Objekt gespeichert ist');
        }
        
        $db = $this->getTable()->getAdapter();
        $select = $db->select();
        
        $id = $db->quote($this->getPrimaryValue(), Zend_Db::INT_TYPE);
        $dir_expr = new Zend_Db_Expr("IF(p2p.from_process_id = ".$id.", 'out', 'in')");
        
        $select->from(
                    array('p2p' => 'process_has_process'), 
                    array('from_process_id', 'to_process_id', 'direction' => $dir_expr))
               ->joinLeft(
                    array('p' => 'process'),
                    "(p2p.from_process_id != ".$id." AND p2p.from_process_id = p.process_id) 
                     OR 
                     (p2p.to_process_id != ".$id." AND p2p.to_process_id = p.process_id)", 
                    array('notation', 'process_id', 'name')
                 )
               ->joinLeft(
                    array('pi' => 'process_interrelations'), 
                    'p2p.process_interrelation_id = pi.process_interrelation_id',
                    array('process_interrelation_id', 'description')
               )
               ->where("p2p.from_process_id = ".$id." OR p2p.to_process_id = ".$id)
        ;
        
        $res = $select->query();
        $interrelations = array();
        while($row = $res->fetch())
        {
            if(!array_key_exists($row['process_interrelation_id'], $interrelations))
            {
                $interrelations[$row['process_interrelation_id']] = array(
                                                                      'description' => $row['description'],
                                                                      'out' => array(),
                                                                      'in' => array()
                                                                    );
            }
            $related_process = ($row['from_process_id'] == $id) ? $row['to_process_id'] : $row['from_process_id'];
            $process_data = array(
                              'notation' => $row['notation'],
                              'process_id' => $related_process
            );
            $interrelations[$row['process_interrelation_id']][$row['direction']][] = $process_data;
        }
        
        return $interrelations;
    }
    
    /**
     * Ruft die getBreadcrumbs Methode der App_Model_Value_TreeAbstract Klasse auf und entfernt
     * den root Eintrag
     * @see library/App/Model/Value/App_Model_Value_TreeAbstract#getBreadcrumbs($options)
     */
    public function getBreadcrumbs($options = null)
    {
        $breadcrumbs = parent::getBreadcrumbs($options);
        unset($breadcrumbs[0]);
        return $breadcrumbs;
    }
}