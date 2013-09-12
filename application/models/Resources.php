<?php
class Default_Model_Resources extends App_Model_Gateway_Abstract
{
    protected $_resourceList = null;
    protected $_resource = App_Acl::ADMIN_RIGHTS_RESOURCES;
    
    /**
     * Überschreibt die fetchAll() Methode des abstrakten Gateways
     * @see Model/Gateway/App_Model_Gateway_Abstract#fetchAll($where, $order, $perpage, $offset)
     */
    public function fetchAll()
    {
        $this->checkAcl(App_Acl::VIEW);
        $resourceList = $this->getResourceList();
        $table = $this->getTable();
        
        //zusatzdaten zu den resourcen aus der datenbank lesen
        $res = $table->fetchAll();
        $resource_data = array();
        foreach($res AS $row)
        {
            $resource_data[$row['resource_id']] = $row->toArray();
        }
        
        //resourcen liste vom acl objekt mit den zusatzdaten aus der datenbank verknüpft
        foreach($resourceList AS $resource)
        {
            //name, id, description
            $resource_id = $resource['resource_id'];
            $name = $resource_id;
            if(array_key_exists($resource_id, $resource_data))
            {
                $resource_name = $resource_data[$resource_id]['resource_name'];
                $resource_description = $resource_data[$resource_id]['resource_description'];
            }
            else
            {
                $resource_name = $resource_id;
                $resource_description = '';
            }
            $resourceList[$resource_id]['resource_name'] = $resource_name;
            $resourceList[$resource_id]['resource_description'] = $resource_description;
        }
        return $this->createResultset($resourceList);
    }
    
    /**
     * Liest einen Datensatz aus. Wenn kein Datensatz in der Datenbank vorhanden werden die 
     * Daten für Namen und ID direkt vom ACL Objekt übernommen 
     * @see Model/Gateway/App_Model_Gateway_Abstract#fetch($id)
     */
    public function fetch($id)
    {
        $this->checkAcl(App_Acl::VIEW);
        $table = $this->getTable();
        $resource_id = $table->getAdapter()->quote($id);
        $res = $table->fetchRow("resource_id = ".$resource_id);
        if($res == null)
        {
            $value = array(
                        'resource_id' => $id,
                        'resource_name' => $id,
                        'resource_description' => ''
                    );
        }
        else
        {
            $value = $res->toArray();
        }
        $value_class = $this->getValueClass();
        return new $value_class($value, array('gateway' => $this));
    }
    
    /**
     * Ermittelt vom ACL Objekt die Resourcen Liste
     * @return array Resourcen Liste
     */
    protected function getResourceList()
    {
        if($this->_resourceList === null)
        {
            $acl = Zend_Registry::get('acl');
            $this->setResourceList($acl->getResourceList());
        }
        return $this->_resourceList;
    }
    
    /**
     * Setzt die Liste der Resourcen
     * @param array $resourceList
     * @return void
     */
    protected function setResourceList(array $resourceList)
    {
        $this->_resourceList = $resourceList;
    }   
}