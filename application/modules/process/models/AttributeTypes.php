<?php
class Process_Model_AttributeTypes extends App_Model_Gateway_OrderedList
{
    protected $_resource = App_Acl::PROCESS_ATTRIBUTETYPES;
    
    /**
     * Erstellt einen Array, der lediglich die Namen der Attribut Typen enthält und 
     * die ID des Attribut Typs als Index 
     * (wird benötigt, um ein Zend_Form_Select Objekt befüllen zu können
     * @return array
     */
    public function fetchAttributeNames()
    {
        $select = $this->getTable()->select();
        $select->from($this->getTableName(), array('process_attribute_type_id', 'name'))
               ->order('name');
        $res = $select->query();
        $values = array(0 => '-- Bitte wählen --');
        foreach($res AS $value)
        {
            $values[$value['process_attribute_type_id']] = $value['name'];
        }
        return $values;
    }
    
    /**
     * 
     * @param int $active id des aktuell ausgewählten Attribut Typs
     * @return array
     */
    public function fetchAttributeTypes($active = null)
    {       
        $select = $this->getTable()->select();
        $active_id = (is_numeric($active)) ? $active : 'NULL';
        
        $select->from($this->getTableName(), 
                        array(
                            '*', 
                            'selected' => new Zend_Db_Expr("IF(process_attribute_type_id = ".$active_id.", 1, 0)")
                        )
                      )
               ->order('position')
               ;
        $res = $select->query();
        
        $filter = new Zend_Filter_Alnum();
        $attributes = array();
        while($row = $res->fetch())
        {
            $row['filtered_name'] = $filter->filter($row['name']);
            $attributes[] = $row;
        }
        
        if(!is_numeric($active))
        {
            $attributes[0]['selected'] = 1;
        }
        return $attributes;
    }
}