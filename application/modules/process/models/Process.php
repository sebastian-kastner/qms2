<?php
/**
 * Gateway für die Prozesse
 * @author kastners
 * @see App_Model_Gateway_Abstract
 */
class Process_Model_Process extends App_Model_Gateway_TreeAbstract
{
    protected $_valueClass = 'Process_Model_Value_Process';
    protected $_resultClass = 'Process_Model_Resultset_Processes';
    protected $_resource = App_Acl::PROCESS;
    
    /**
     * Liest die Prozess Liste aus. Es werden nur Prozesse der ersten Ebene berücksichtigt. Die Prozesse
     * werden mit dem dazugehörigen Prozessattribut ausgelesen und nach der Reihenfolge sortiert, nach der 
     * Reihenfolge der Prozesstypen und nach der reihenfolge im Prozessbaum geordnet
     * 
     * @return array
     */
    public function getProcessList()
    {
        //zugriffsrechte überprüfen
        $this->checkAcl(App_Acl::VIEW);
        
        $table = $this->getTable();
        $select = $table->getByLevelSelect(1, array(App_Db_Tree::WHERE => App_Db_Tree::NODE_ALIAS.'.is_active = 1'));
        
        $select->joinLeft(
                    array('pt' => 'process_types'), 
                          'node.process_type_id = pt.process_type_id', 
                            array('type_name' => 'name', 'process_type_id')
                 );
        $select->order('pt.position');
        $select->order("node.".$this->getTable()->getLeft());
        
        $result = $select->query();
        $processList = array();
        $lastProcessTypeId = 0;
        
        foreach($result AS $row)
        {
            $processTypeId = $row['process_type_id'];
            if($lastProcessTypeId != $processTypeId)
            {
                $processList[$processTypeId] = array(
                                                          'process_type_id' => $processTypeId,
                                                          'type_name' => $row['type_name'],
                                                          'processes' => array());    
            }
            
            $lastProcessTypeId = $processTypeId;
            
            unset($row['process_type_id']);
            unset($row['type_name']);
            $processList[$processTypeId]['processes'][] = $row;
            
        }
        return $processList;
    }
    
    /**
     * Sucht nach einem Prozess, der im Namen den Suchbegriff $search enthält
     * @param $search
     * @return array
     */
    public function processSearch($search)
    {
        $select = $this->getTable()->select();
        $search = $this->getTable()->getAdapter()->quote("%".$search."%");
        $select->from('process', array('name', 'notation', 'process_id'))
               ->where("name LIKE ".$search);
               
        $result = $select->query();
        
        return $result->fetchAll();
    }
    
    /**
     * Liest zu genau einem Prozess genau ein Attribut aus
     * Wird keine attribute_id übergeben wird der erste übergebene Parameter als 
     * pha_id (primärschlüssel der process_has_attribute tabelle) interpretiert.
     * @param int $process_id
     * @param int $attribute_id
     */
    public function getProcessAttribute($process_id, $attribute_id = false)
    {
        $db = $this->getTable()->getAdapter();
        $select = $db->select();
        
        $process_id = $db->quote($process_id, Zend_Db::INT_TYPE);
        if($attribute_id)
        {
            $attribute_id = $db->quote($attribute_id, Zend_Db::INT_TYPE);
        }
        
        $select
               ->from(
                   array('pa' => 'process_attributes'),
                   array('process_attribute_id', 'name')
                 )
               ->joinLeft(
                   array(
                   'pha' => 'process_has_attribute'),
                   '(pa.process_attribute_id = pha.process_attribute_id
                     AND
                     pha.process_id = '.$process_id.')',
                   array('attribute_value', 'pha_id')
                 )
               ->where('pa.is_active = 1')
               ->limit(1);
               
        if(!$attribute_id)
        {
            $select->where('pha.pha_id = '.$process_id.' OR pha.pha_id IS NULL');
        }
        else
        {
            $select->where('pa.process_attribute_id = '.$attribute_id)
                   ->where('pha.process_id = '.$process_id.' OR pha.process_id IS NULL');
        }
        
        $result = $select->query();
        
        $attrib = $result->fetchAll();
        
        if(count($attrib) > 0)
        {
            return $attrib[0];    
        }
        return array();
    }
}