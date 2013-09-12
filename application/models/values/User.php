<?php
/**
 * Value Klasse für die User
 * @uses App_Model_Value_Abstract
 * @author kastners
 * @package Admin
 * @subpackage Value
 *
 */
class Default_Model_Value_User extends App_Model_Value_Abstract
{
    protected $_tableClass = 'Default_Model_DbTable_AclUsers';
    protected $_formClass = 'Admin_Model_Form_User';
    protected $_entityName = 'Benutzer';

    /**
     * Gibt die IDs der Rollen des Benutzers in einem Array zurück
     * @param boolean $active_roles_only (opt) standardmäßig true. wenn false werden auch deaktivierte rollen ausgelesen
     * @return array
     */
    public function getRoles($active_roles_only = true)
    {
        if($this->checkPrimary())
        {
            return $this->getGateway()->getRoles($this->getPrimaryValue(), $active_roles_only);
        }
        return array();
    }

    /**
     * (non-PHPdoc)
     * @see library/App/Model/Value/App_Model_Value_Abstract#save()
     */
    public function save()
    {
        //password hashen
        $this->_data['password'] = App_Password::hash($this->pswd);
        //versuch benutzer zu speichern. bei fehler: abbruch!
        $return_val = parent::save();
        if(!$return_val) {
            return false;
        }
        $this->saveRoles();
        
        return $return_val;
    }
    
    /**
     * Speichert die Rollen für den Benutzer. Die Rollen, die dem Benutzer zugeschrieben werden sollen stehen in 
     * $this->roles (Bevor die saveRoles() Methode aufgerufen werden kann muss also ein Formular abgeschickt worden 
     * (entweder Admin_Model_Form_User oder Admin_Model_Form_UserRoles) sein)
     * @return Default_Model_Value_User
     * @throws App_Model_Exception
     */
    public function saveRoles()
    {
        if(!array_key_exists('roles', $this->_data))
        {
            throw new App_Model_Exception("Die Benutzerrollen können nicht gespeichert werden, da keine Rollen über ein Formular übergeben wurden!");
        }
        //rollen ids in einen array schreiben
        $roles = trim($this->roles);
        $new_ids = ($roles != "") ? explode(" ", $roles) : array();
        
        //dem benutzer die rollen zuweisen
        $userHasRoles = new Default_Model_UserHasRoles();
        $userHasRoles->saveUserRoles($this->getPrimaryValue(), $new_ids, $this->getRoles(false));
        
        return $this;
    }
}