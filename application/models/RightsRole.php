<?php
/**
 * Gateway für die Rechte auf Rollenebene. 
 * @author kastners
 */
class Default_Model_RightsRole extends Default_Model_Rights
{
    protected $_rightType = self::ROLE;

    /**
     * Liest die "kompletten" Rechte aus. Im Gegensatz zur getRights() Methode werden hier auch für Einträge, die 
     * implizit dadurch erlaubt sind, dass das Elternelement erlaubt ist, erstellt. 
     * Wird benötigt, um im Adminbereich die Checkboxen mit den Feldern, die erlaubt sind, anzuwählen
     * Die Einträge, die im Array stehen, sind erlaubt, was nicht im Array steht ist nicht erlaubt
     * 
     * @param int $id
     * @return array
     */
    public function getFullRights($id)
    {
        $this->checkAcl(App_Acl::VIEW);
        if(!is_numeric($id))
        {
            throw new App_Model_Exception('Ungültige Rollen ID übergeben!');
        }
         
        $rights = array();
        $acl = $this->getAcl();
        $resourceList = $acl->getResourceList();
        $privileges = $acl->getPrivileges();

        if(!$acl->hasRole($id))
        {
            $acl->createRole($id);
        }
        foreach($resourceList AS $resource)
        {
            $resource_id = $resource['resource_id'];
            foreach($privileges AS $privilege_id => $privilege)
            {
                if($acl->roleIsAllowed($id, $resource['resource_id'], $privilege_id))
                {
                    $rights[$resource['resource_id']][$privilege_id] = $privilege_id;
                }
            }
        }
        return $rights;
    }

    /**
     * Gibt das Resultat des Queries nach den Rechten zurück
     * @param $id
     * @return Zend_Db_Statement_Pdo
     */
    protected function getRightsRes($id)
    {
        //gibt null zurück, wenn ein array ohne ids übergeben wurde
        if(is_array($id) && count($id) < 1)
        {
            return null;
        }
        $where = '';
        if(is_array($id))
        {
            $ids = array();
            foreach($id AS $id)
            {
                   $ids[] = $this->getTable()->getAdapter()->quote($id, Zend_Db::INT_TYPE);
            }
            $where = $this->getRightTypeCol().' = '.implode(' OR '. $this->getRightTypeCol(). ' = ', $ids);
        }
        elseif(is_int((int)$id))
        {
            $where = $this->getRightTypeCol().' = '.$this->getTable()->getAdapter()->quote($id, Zend_Db::INT_TYPE);
        }
        
        $select = $this->getTable()->select();
        $select->from($this->getTableName(), array('resource_id', 'privilege_id', 'allow', $this->getRightTypeCol()))
               ->where($where)
               ->order('resource_id');
        return $select->query();
    }
    
    /**
     * Erstellt eine neue Rolle mit der übergebenen ID, falls diese noch nicht im ACL Objekt vorhanden ist 
     * @see application/models/Default_Model_Rights#createAclRole()
     */
    public function createAclRole($id)
    {
        $acl = $this->getAcl();
        if(!$acl->hasRole($id))
        {
            $acl->createRole($id);
        }
    }
    
    /**
     * Erstellt eine temporäre Rolle, deren Rechte gleich der Rechte des zu speichernden Benutzers bzw. der 
     * zu speichernden Rolle sind
     * @param int $id Rollen ID
     * @return String Name der temporären Rolle
     */
    protected function createTmpRole($id)
    {
        $acl = $this->getAcl();
        
        //parent_id auslesen
        $model = new Default_Model_Roles();
        $parent = $model->getDirectParent($id);
        $parent_id = $parent[$this->getRightTypeCol()];
        
        //neue, temporäre rollen erstellen, mit der ermittelt wird, welche einträge in die db müssen
        $tmp_role = $this->createTmpRoleName();
        $acl->addRole($tmp_role, App_Acl::getRoleId($parent_id));
        return $tmp_role;
    }
}
