<?php

class Default_Model_UserHasRoles extends App_Model_Gateway_Abstract
{
    protected $_resource = App_Acl::ADMIN_RIGHTS_ROLES;
    /**
     * Gibt die IDs der Rollen eines Benutzers zurück
     * @param int $user_id
     * @param boolean $active_roles_only (opt) standardmäßig true. wenn false werden auch deaktivierte rollen ausgelesen
     * @return array
     */
    public function getUserRoles($user_id, $active_roles_only = true)
    {
        //$this->checkAcl(App_Acl::VIEW); --> acl check gibt bei login probleme, 
        //da nicht mit UserHasRoles sondern mit Users Objekt kommuniziert wird und das "abschalten" somit nicht ohne weiteres
        //auf das UserHasRoles Objekt übertragbar ist
        $user_id = $this->getTable()->getAdapter()->quote($user_id, Zend_Db::INT_TYPE);
        
        if(!$active_roles_only)
        {
            $select = $this->getTable()->select();
            $select->from($this->getTable(), array('role_id'))
                   ->where("user_id = ".$user_id);
        }
        else
        {
            $select = $this->getTable()->getAdapter()->select();
            $select
               ->from(
                  array('u2r' => 'acl_user_has_role')
                 )
               ->joinLeft(
                  array('r' => 'acl_roles'),
                  '(u2r.role_id = r.role_id)',
                  array('role_id')
                 )
               ->where('r.is_active = 1');
        }
        $result = $select->query();
        $ids = array();
        while($row = $result->fetch())
        {
            $ids[] = $row['role_id'];
        }
        return $ids;
    }

    /**
     * Verknüpft einen Benutzer mit einer Liste von Rollen
     * @param int $user_id Benutzer ID
     * @param array $role_ids Rollen IDs, die dem Benutzer zugewiesen werden sollen
     * @param array $old_ids Die Rollen IDs, die dem Benutzer vor dem Speichervorgang zugewiesen sind
     * @return Default_Model_UserHasRoles
     */
    public function saveUserRoles($user_id, $role_ids, $old_ids)
    {
        $this->checkAcl(App_Acl::EDIT);
        $user_id = $this->getTable()->getAdapter()->quote($user_id, Zend_Db::INT_TYPE);

        $to_add = array();
        $to_del = array();

        //wenn in $new_ids aber nicht in $old_ids => add
        for($i=0;$i<count($role_ids);$i++)
        {
            if(!in_array($role_ids[$i], $old_ids))
            {
                $id = $this->getTable()->getAdapter()->quote($role_ids[$i], Zend_Db::INT_TYPE);
                //datensatz hinzufügen
                $this->getTable()->insert(array(
                    'role_id' => $id,
                    'user_id' => $user_id
                ));
            }
        }
        //wenn in $old_ids aber nicht in $role_ids => delete
        for($i=0;$i<count($old_ids);$i++)
        {
            if(!in_array($old_ids[$i], $role_ids))
            {
                //delete
                $to_del[] = $this->getTable()->getAdapter()->quote($old_ids[$i], Zend_Db::INT_TYPE);
            }
        }
        //löschen
        if(count($to_del) > 0)
        {
            $where = "user_id = ".$user_id." AND role_id IN (".implode($to_del, ", ").")";
            $this->getTable()->delete($where);
        }

        return $this;
    }
}