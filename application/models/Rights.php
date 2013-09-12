<?php
/**
 * Gateway für die Rechte. Das Gateway ist abstrakt und es gibt zwei Klassen, die das Gateway erweitern.
 * Eine Klasse für Rechte für Benutzer und eine Klasse für Rechte für Rollen.
 * @author kastners
 *
 */
abstract class Default_Model_Rights extends App_Model_Gateway_Abstract
{
    protected $_valueClass = 'Default_Model_Value_Right';
    protected $_resource = App_Acl::ADMIN_RIGHTS_PRIVILEGES;

    /**
     * Konstante, die Rechte für Benutzer identifiziert
     * @var string
     */
    const USER = 'user';

    /**
     * Konstante, die Rechte für Rollen identifiziert
     * @var string
     */
    const ROLE = 'role';

    /**
     * Rechtetyp. Handelt es sich bei den Rechten um Rechte für eine Rolle oder für einen einzelnen Benutzer?
     * @var string
     */
    protected $_rightType = null;

    /**
     * Spalte, in der die User IDs gespeichert werden (falls es sich um Rechte für einen Benutzer handelt)
     * @var string
     */
    protected $_userTypeId = 'user_id';

    /**
     * Spalte, in der die Role IDs gespeichert werden (falls es sich um Rechte für einen Benutzer handelt)
     * @var unknown_type
     */

    protected $_roleTypeId = 'role_id';

    /**
     * Liest die "kompletten" Rechte aus. Im Gegensatz zur getRights() Methode werden hier auch für Einträge, die 
     * implizit dadurch erlaubt sind, dass das Elternelement erlaubt ist, erstellt.
     * Wird benötigt, um im Adminbereich die Checkboxen mit den Feldern, die erlaubt sind, anzuwählen
     * Die Einträge, die im Array stehen, sind erlaubt, was nicht im Array steht ist nicht erlaubt
     *
     * @param int $id
     * @return array
     */
    abstract public function getFullRights($id);

    /**
     * Gibt das Resultat des Queries nach den Rechten zurück
     * @param $id
     * @return Zend_Db_Statement_Pdo
     */
    abstract protected function getRightsRes($id);
    
        /**
     * Erstellt im ACL Objekt eine neue Rolle oder einen neuen Benutzer
     * @return Default_Model_Rights
     */
    abstract public function createAclRole($id);

    /**
     * Erstellt eine temporäre Rolle, deren Rechte gleich der Rechte des zu speichernden Benutzers bzw. der 
     * zu speichernden Rolle sind
     * @return String Name der temporären Rolle
     */
    abstract protected function createTmpRole($id);
    
    /**
     * Gibt den Array mit den Rechten für eine Rolle (wenn $id ein int wert ist)
     * oder für mehrere Rollen (wenn $id ein array ist) gruppiert nach der Resource zurück
     *
     * Über den Parameter $group_by_role kann angegeben werden, wie der ausgabe array gruppiert werden
     * soll. Ist $group_by_role auf true (standard), dann wird nach der Rolle gruppiert. Der Ausgabearray
     * hat dann folgenden Aufbau:
     *   rights[id][resource_id][privilege_id] = allow
     * Wird group_by_role auf false gesetzt ensteht folgender output:
     *   rights[resource_id][privilege_id] = allow
     *
     * @param int|array $id
     * @param bool $group_by_role (opt) gibt an, ob der ausgabe array nach der rollen id oder
     *                            nur nach der resourcen id gruppiert werden soll
     * @return array
     */
    public function getRights($id, $group_by_role = true)
    {
        $this->checkAcl(App_Acl::VIEW);
        if(!is_numeric($id) && !is_array($id))
        {
            throw new App_Model_Exception('Beim Auslesen der Rechte wurde eine ungültige ID übergeben!');
        }

        $rights = array();
        $res = $this->getRightsRes($id);
        if($res == null)
        {
            return array();
        }

        while($row = $res->fetch())
        {
            if($group_by_role)
            {
                $rights[$row[$this->getRightTypeCol()]][$row['resource_id']][$row['privilege_id']] = $row['allow'];
            }
            else
            {
                $rights[$row['resource_id']][$row['privilege_id']] = $row['allow'];
            }
        }
        return $rights;
    }
    
    /**
     * Speichert die Rechte für eine bestimmte Rolle ab
     * @param int $id ID der Rolle, zu der die Rechte gespeichert werden sollen
     * @param array $rights
     * @return void
     */
    public function saveRights($id, array $rights)
    {
        $this->checkAcl(App_Acl::EDIT);
        $db = $this->getTable()->getAdapter();
        $id = $db->quote($id, Zend_Db::INT_TYPE);
        $acl = $this->getAcl();
                
        //rolle erstellen, falls noch nicht vorhanden
        $this->createAclRole($id);      
        $tmp_role = $this->createTmpRole($id);
        
        $resourceList = $acl->getResourceList();
        $privileges = $acl->getPrivileges();
        $data = array();
        //iterieren über alle resourcen und privilegien (ergo über das formular)
        foreach($resourceList AS $resource)
        {
            $resource_id = $resource['resource_id'];
            foreach($privileges AS $privilege_id => $privilege)
            {
                //ermitteln, ob das privileg für die resource im formular ausgewählt wurde
                $checked = (array_key_exists($resource_id, $rights) && in_array($privilege_id, $rights[$resource_id]));
                $allowed = $acl->isAllowed($tmp_role, $resource_id, $privilege_id);
                //wenn der status der temporären rolle nicht gleich dem status im formular ist müssen werte in db geschrieben werden
                if($allowed != $checked)
                {
                    $allow = ($checked) ? 1 : 0;
                    $data[] = array(
                                'resource_id' => $resource_id,
                                'privilege_id'  => $privilege_id,
                                $this->getRightTypeCol() => $id,
                                'allow'         => $allow
                              );
                    //wenn im formular ausgewählt => allow
                    if($checked)
                    {
                        $acl->allow($tmp_role, $resource_id, $privilege_id);
                    }
                    //wenn im formular nicht ausgewählt => deny
                    else
                    {
                        $acl->deny($tmp_role, $resource_id, $privilege_id);
                    }
                }
            }
        }
        //temporäre rolle entfernen
        $acl->removeRole($tmp_role);

        //eintragen der "neuen" rechte in die datenbank
        //vor dem eintragen werden die alten rechte für rolle/benutzer (nur die rolle/benutzer selbst, nicht für parents) gelöscht
        $this->getTable()->delete($this->getRightTypeCol()." = ".$id);
        foreach($data AS $row)
        {
            $value = $this->getValue($row);
            $value->save();
        }
        return true; 
    }

    public function createTmpRoleName()
    {
        $acl = $this->getAcl();
        $tmp_role = md5(microtime(true));
        while($acl->hasRole($tmp_role)) //falls der hash schon existiert (unwahrscheinlich)
        {
            $tmp_role = md5($tmp_role);
        }
        return $tmp_role;
    }
    
    /**
     * Gibt den Rechtetyp zurück
     * @return string
     */
    public function getRightType()
    {
        return $this->_rightType;
    }

    /**
     * Gibt den Namen der Spalte zurück, in der die ID des RechteTyps gespeichert wird. Handelt es sich um ein
     * Gateway für Benutzerrechte, dann gibt die Methode den Namen der Spalte zurück, in der die user_id gespeichert
     * wird, ist es ein Gateway für Rollenrechte wird der Name der Spalte zurückgegeben, in der die Rollen ID 
     * gespeichert wird
     * @return string
     */
    public function getRightTypeCol()
    {
        return ($this->getRightType() == 'user') ? $this->_userTypeId : $this->_roleTypeId;
    }
}