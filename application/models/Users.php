<?php 

class Default_Model_Users extends App_Model_Gateway_Abstract
{
    protected $_perpage = 20;
    protected $_resource = App_Acl::ADMIN_USERS;
    
    /**
     * Liest die Rollen zu einer Benutzer ID aus
     * @param int $user_id
     * @param boolean $active_roles_only (opt) standardmäßig true. wenn false werden auch deaktivierte rollen ausgelesen
     * @return array
     */
    public function getRoles($user_id, $active_roles_only = true)
    {
        if(!is_numeric($user_id))
        {
            throw new App_Model_Exception("Beim Erstellen der Benutzerrollen wurde keine gültige Benutzer ID übergeben!");
        }
        $model = new Default_Model_UserHasRoles();
        $id = $this->getTable()->getAdapter()->quote($user_id, Zend_Db::INT_TYPE);
        //acl wird in $model->getUserRoles überprüft
        $result = $model->getUserRoles($id, $active_roles_only);
        return $result;
    }

    /**
     * Überprüft, ob Benutzer und Passwort zusammenpassen.
     * Gibt im Erfolgsfall die ID des Benutzers zurück. Falls die Zugangsdaten nicht stimmen 
     * wird false zurückgegeben
     * 
     * @param string $username benutzername
     * @param string $password benutzerpasswort (in reintext, nicht gehasht)
     * @return bool|int
     */
    public function checkCredentials($username, $password)
    {
        $password = App_Password::hash($password);
        $username = $this->getTable()->getAdapter()->quote($username);
        $password = $this->getTable()->getAdapter()->quote($password);
        $where = "username = ".$username." AND password = ".$password;
        
        $select = $this->getTable()->getAdapter()->select();
        $select->from($this->getTableName(), array('user_id'))
               ->where($where)
               ->limit(1);
        $res = $select->query();
        if($user = $res->fetch())
        {
            return $user['user_id'];
        }
        return false;
    }
}