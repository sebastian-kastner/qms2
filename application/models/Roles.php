<?php
class Default_Model_Roles extends App_Model_Gateway_TreeAbstract
{
    protected $_perpage = 20;
    protected $_resource = App_Acl::ADMIN_RIGHTS_ROLES;
    
    /**
     * Id des Root Datensatzes (hier: ID der Gastrolle)
     * @var unknown_type
     */
    protected $_root_id = 1;
    protected $_columns = array('lsf_role_id');
    
    /**
     * Alle Rollen auslesen
     * @param array $options zusätzliche Optionen 
     */
    public function fetchAll($show_deactive = false)
    {
        $this->checkAcl(App_Acl::VIEW);
        $expr = new Zend_Db_Expr("IF(".App_Db_Tree::NODE_ALIAS.".lsf_role_id IS NOT NULL, 'LSF', 'QM') AS role_type");
        
        $options = array(
                      App_Db_Tree::COLUMNS => array(App_Db_Tree::NODE_ALIAS.".*", $expr),
                   );
        $where = null;
        if(!$show_deactive)
        {
            $where = 'is_active = 1';
            $options[App_Db_Tree::WHERE] = App_Db_Tree::NODE_ALIAS.".".$where;
        }
        
        $table = $this->getTable();        
        if($this->_usePaginator == true)
        {
            $perpage = $this->_perpage;
            $offset = ($this->getPage()-1) * $perpage;
            $options[App_Db_Tree::LIMIT] = $perpage;
            $options[App_Db_Tree::OFFSET] = $offset;
            $this->setPaginator($perpage, $offset, $where);
        }
       
        $roles = $this->getTree($options);
        $roles = $this->createResultset($roles);
        return $roles; 
    } 
    
    /**
     * Liest Rollen aus, die mit $substring beginnen
     * @param string $substring
     * @return array
     */
    public function roleSearch($substring)
    {
        $this->checkAcl(App_Acl::VIEW);
        if(strlen($substring) > 0)
        {
            $this->setUsePaginator(false);
            $substring = $this->getTable()->getAdapter()->quote("%".$substring."%");
            $where = 'role_name LIKE '.$substring;
            return parent::fetchAll($where)->toArray();
        }
        else
        {
            return array();
        }
    }
    
    /**
     * Gibt die Rollen zu einer Liste von Rollen IDs zurück. Die Rollen IDs können entweder als Array oder
     * als ein durch Komma getrennten String übergeben werden
     * @param string|array $ids
     * @return array
     */
    public function getRolesByIds($ids)
    {
        $this->checkAcl(App_Acl::VIEW);
        $id_list = '';
        if(is_array($ids)) {
            $id_list = implode(', ', $ids);
        }
        $select = $this->getTable()->select();
        $search = $this->getTable()->getAdapter()->quote($ids);
        $select->from($this->getTable(), array('role_name', 'role_id'))
               ->where("role_id IN (".$ids.")");
               
        $result = $select->query();
        return $result->fetchAll();
    }
}