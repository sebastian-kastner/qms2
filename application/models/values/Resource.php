<?php
/**
 * Value Klasse für die Resourcen
 * @uses App_Model_Value_Abstract
 * @author kastners
 * @package Admin
 * @subpackage Value
 *
 */
class Default_Model_Value_Resource extends App_Model_Value_Abstract
{   
    protected $_tableClass = 'Default_Model_DbTable_AclResources';
    protected $_formClass = 'Admin_Model_Form_Resource';
    protected $_entityName = 'Resource';
    
    /**
     * 
     * @see Model/Value/App_Model_Value_Abstract#save()
     */
    public function save()
    {
        //TODO: ACL
        $validator = $this->getForm();
        $idField = $this->getPrimary();
        
        if($validator->isValid($this->_data))
        {
            $table = $this->getTable();
            $select = $table->select();
            $select->from($this->getTableName(), array('num_rows' => 'COUNT(*)'))
                   ->where($idField." = ?", $this->_data[$idField])
                   ->limit(1);
            $res = $select->query();
            $row = $res->fetch();
            
            //$data = $this->stripUnallowedKeys();
            //$data = $this->clearProtectedProperties($data);
            $data = $this->_data;
            if($row['num_rows'] == 0)
            {
                return $this->insert(true);
            }
            else
            {
                return $this->update();
            }
        }
        else
        {
            return false;
        }
    }
}