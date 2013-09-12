<?php
class Process_Model_ProcessAttributes extends App_Model_Gateway_OrderedList
{
    protected $_usePaginator = false;
    
    protected $_groupListBy = 'process_attribute_type_id';
    protected $_resource = App_Acl::PROCESS_ATTRIBUTES;
    
    /**
     * Liest die Prozessattribute Gruppiert nach den Prozesstypen, zu denen die Prozessattribute gehören aus
     * @return array
     */
    public function fetchGroupedByTypes()
    {
        $select = $this->getTable()->getAdapter()->select();
        $select
               ->from(
                   array('pa' => 'process_attributes'),
                   array('process_attribute_id',
                      'attribute_name' => 'name',
                      'attribute_position' => 'position'
                   )
                 )
               ->joinLeft(
                   array('pat' => 'process_attribute_types'),
                   '(pat.process_attribute_type_id = pa.process_attribute_type_id)',
                   array(
                      'process_attribute_type_id',
                      'type_name' => 'name',
                      'type_position' => 'position'
                   )
                 )
               ->order(array('type_position', 'attribute_position'));
               //->where('is active und alles..')
        $res = $select->query();
        
        $types = array();
        
        $last_type_id = null;
        
        foreach($res AS $row)
        {
            if($last_type_id != $row['process_attribute_type_id'])
            {
                $types[$row['process_attribute_type_id']] = array(
                              'type_name' => $row['type_name'],
                              'type_position' => $row['type_position'],
                              'process_attribute_type_id' => $row['process_attribute_type_id'],
                              'attributes' => array()
                            );
                $last_type_id = $row['process_attribute_type_id'];
            }
            
            $types[$row['process_attribute_type_id']]['attributes'][] = array(
                              'attribute_name' => $row['attribute_name'],
                              'attribute_position' => $row['attribute_position'],
                              'process_attribute_id' => $row['process_attribute_id']
                           );
        } 
        return $types;
    }
}