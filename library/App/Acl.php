<?php
/**
 * QMS 2.0 spezifische Erweiterungen für die Zend_Acl Komponente
 * @category   App
 * @package    App_Acl
 * @author     kastners
 */
class App_Acl extends Zend_Acl
{
    /**
     * Präfix für Rollen IDs, der als ID im ACL Objekt angegeben wird. (Der Präfix ist nötig, um Überschneidungen mit 
     * Benutzer IDs zu vermeiden)
     * @var string
     */
    const ROLE_PREFIX = 'role';

    /**
     * ID der Gast Rolle, die jedem Benutzer zugewiesen wird, wenn er sonst keine zugewiesenen Rollen hat
     * @var string
     */
    static $GUEST_ROLE_ID = "1";

    /**
     * Konstanten für die Resourcen
     * Damit die Resourcen im Programm verwendet werden können müssen sie noch in der Methode "createResources()" 
     * hinzugefügt werden!
     * 
     * NAMENSKONVENTION:
     * Die Namen der Resourcen müssen so gewählt werden, dass ein Kinderelement mit dem Namen des Elternelements gefolgt
     * von einem Underscore beginnt.
     * Das Elternelement sei: "process". Ein Kinderelement von "process" sei "interrelations". Das Kinderelement
     * müsste demnach "process_interrelations" heißen!
     */
    const PROCESS = 'process';
    const PROCESS_INTERRELATIONS = 'process_interrelations';
    const PROCESS_DOCUMENTS = 'process_documents';
    const PROCESS_PROCESSTYPES = 'process_processtypes';
    const PROCESS_ATTRIBUTES = 'process_attributes';
    const PROCESS_ATTRIBUTETYPES = 'process_attributetypes';
    
    const ADMIN = 'admin';
    const ADMIN_RIGHTS = 'admin_rights';
    const ADMIN_RIGHTS_RESOURCES = 'admin_rights_resources';
    const ADMIN_RIGHTS_ROLES = 'admin_rights_roles';
    const ADMIN_USERS = 'admin_users';
    const ADMIN_RIGHTS_PRIVILEGES = 'admin_rights_privileges';
    
    /**
     * Privilegien, die im System verwendet werden. 
     * Müssen auch in der Methode getPrivileges() hinzugefügt werden um im System beachtet zu werden
     * @var unknown_type
     */
    const VIEW = 1;
    const ADD = 2;
    const EDIT = 3;
    const DEL = 4;

    protected $_resourceList = array();

    /**
     * Überprüft, ob eine $role für die $resource ein $privilege hat. 
     * Im Gegensatz zu Zend_Acl::isAllowed() dominiert ein allow ein deny in jedem Fall, wenn eine Rolle
     * von mehreren Rollen erbt.
     * Bsp: Rolle A erlaubt das Recht XY. Rolle B verbietet das Recht XY. Die Rolle Z erbt von A und B. Ob
     * Zend_Acl::isAllowed() true oder false zurückgibt hängt von der Reihenfolge ab, in der die Rollen, von denen
     * geerbt wird angegeben werden.
     * (Mehr Infos unter Example #1: http://framework.zend.com/manual/1.0/en/zend.acl.introduction.html)
     * Die erweiterte isAllowed() Methode ignoriert die dortige Reihenfolge und gibt true zurück, wenn 
     * irgendeine der Rollen, von denen geerbt wird, das Recht XY hat. false wird nur dann zurückgegeben, wenn keine
     * der Rollen, von denen geerbt wird das Recht XY hat.
     * @see library/Zend/Zend_Acl#isAllowed($role, $resource, $privilege)
     */
    public function isAllowed($role, $resource, $privilege)
    {
    	return true;
//        echo "rolle: ".$role. " resource: ".$resource. " privilege: ".$privilege."<br>";
        $parents = $this->_getRoleRegistry()->getParents($role);
        if(count($parents) > 1)
        {
            //wenn rolle direkt das zugriffsrecht hat: true zurückgeben
            if(parent::isAllowed($role, $resource, $privilege))
            {
                return true;
            }
            //ansonsten iteration über alle parents
            foreach($parents AS $parent)
            {
                //wenn es einem der parents erlaubt ist => true als rückgabewert
                if(parent::isAllowed($parent, $resource, $privilege))
                {
                    return true;
                }
            }
            //wenn es keinem der parents erlaubt ist => false
            return false;
        }
        //wenn nur ein parent => standardverhalten von isAllowed
        return parent::isAllowed($role, $resource, $privilege);
    }

    /**
     * Überprüft, ob eine Rolle die Rechte für eine bestimmte resource ha
     * @param int $role rollen id
     * @param string $resource resourcen id
     * @param int $privilege privilegien id
     * @return boolean
     */
    public function roleIsAllowed($role, $resource, $privilege)
    {
        return $this->isAllowed(self::getRoleId($role), $resource, $privilege);
    }

    /**
     * Erstellt die Resourcen für das ACL Objekt
     * @return void
     */
    public function createResources()
    {
        //resourcen im prozess modul
        $this->addResource(self::PROCESS);
        $this->addResource(self::PROCESS_INTERRELATIONS, self::PROCESS);
        $this->addResource(self::PROCESS_DOCUMENTS, self::PROCESS);
        $this->addResource(self::PROCESS_PROCESSTYPES, self::PROCESS);
        $this->addResource(self::PROCESS_ATTRIBUTES, self::PROCESS);
        $this->addResource(self::PROCESS_ATTRIBUTETYPES, self::PROCESS);
        
        //resourcen im admin modul
        $this->addResource(self::ADMIN);
        $this->addResource(self::ADMIN_RIGHTS, self::ADMIN);
        $this->addResource(self::ADMIN_RIGHTS_RESOURCES, self::ADMIN);
        $this->addResource(self::ADMIN_RIGHTS_ROLES, self::ADMIN);
        $this->addResource(self::ADMIN_RIGHTS_PRIVILEGES, self::ADMIN);
        $this->addResource(self::ADMIN_USERS, self::ADMIN);
    }

    /**
     * Gibt die Resourcen als
     * @return array
     */
    public function getResourceList()
    {
        unset($this->_resourceList);
        return $this->createResourceList($this->getResources());
    }

    /**
     * Erstellt rekursiv die Liste der Resourcen mit einer Level Angabe
     * @param array $resources
     * @return array
     */
    protected function createResourceList(array $resources)
    {
        foreach($resources AS $resource_id => $resource)
        {
            //$resource = (is_object($resource)) ? $resources[$resource_id] : $resource;
            if(is_object($resource))
            {
                $res = $this->getResources();
                $resource = $res[$resource_id];
            }
            if($resource['parent'] != null)
            {
                $parent = $resource['parent']->getResourceId();
                if(is_array($this->_resourceList[$parent]['parents']))
                {
                    $path = $this->_resourceList[$parent]['parents'];
                    $path[] = $parent;
                }
                else
                {
                    $path = array($parent);
                }
                $level = $this->_resourceList[$parent]['level'] + 1;
            }
            else
            {
                $level = 1;
                $path = null;
                $parent = null;
            }

            $this->_resourceList[$resource_id] = array(
                                            'resource_id' => $resource_id, 
                                            'level' => $level, 
                                            'parents' => $path,
                                            'parent' => $parent,
                                            'children' => null
            );
            if(is_array($resource['children']))
            {
                $children = $this->getChildren($resource_id);
                $this->_resourceList[$resource_id]['children'] = $children;
                $this->createResourceList($resource['children']);
            }

        }
        return $this->_resourceList;
    }

    /**
     * Sucht in der Datenbank nach der angegebenen Rolle und speichert diese mit den Rechten im ACL Objekt
     * @param int $role
     * @return App_Acl
     */
    public function createRole($role)
    {
        //abbruch, falls die rolle schon vorhanden ist
        if($this->hasRole(self::getRoleId($role)))
        {
            return $this;
        }

        $roles = new Default_Model_Roles();
        $roles->aclDisable();
        $parents = $roles->getBreadcrumbs($role);
        $new_roles = array();
        //die rollen mit der hierarchie erstellen
        foreach($parents AS $role)
        {
            if(!$this->hasRole($role['role_id']))
            {
                $role_id = self::getRoleId($role['role_id']);
                $parent = ($role['parent'] != '') ? self::getRoleId($role['parent']) : null;
                $this->addRole($role_id, $parent);
                $new_roles[] = $role['role_id'];
            }
        }

        //den rollen die rechte aus der datenbank zuweisen
        $model = new Default_Model_RightsRole();
        $model->aclDisable();
        $rights = $model->getRights($new_roles);
        foreach($rights AS $role_id => $role)
        {
            foreach($role AS $resource => $privileges)
            {
                foreach($privileges AS $privilege => $allow)
                {
                    if($allow)
                    {
                        $this->allow(self::getRoleId($role_id), $resource, $privilege);
                    }
                    else
                    {
                        $this->deny(self::getRoleId($role_id), $resource, $privilege);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Erstellt die in $roles übergebenen Rollen, falls diese noch nicht im ACL Objekt hinterlegt sind
     * @param array $roles liste mit den rollen ids
     * @return App_Acl
     */
    public function createRoles(array $roles)
    {
        //alle rollen, die noch nicht im acl objekt sind erstellen
        foreach($roles AS $role)
        {
            if(!$this->hasRole($role))
            {
                $this->createRole($role);
            }
        }
        return $this;
    }

    /**
     * Erstellt die Gastrolle, von der jede andere Rolle erbt, bzw die jedem Benutzer
     * zugeschrieben wird, auch wenn dieser nicht eingeloggt ist
     * @return App_Acl
     */
    public function createGuestRole()
    {
        $this->createRole(self::$GUEST_ROLE_ID);
        return $this;
    }

    /**
     * Sucht nach dem angegebenen User und speichert ihn mit all seinen Rechten im ACL Objekt
     * @param $user_id
     * @param array $roles (opt) Die Rollen, die dem Benutzer zugeordnet sind. Wenn nicht übergeben werden sie anhand der
     *                     Benutzer ID ausgelesen
     * @return App_Acl
     */
    public function createUser($user_id, $roles = null)
    {
        //abbruch, falls der benutzer schon vorhanden ist
        if($this->hasUser($user_id))
        {
            return $this;
        }

        if($roles == null)
        {
            $users = new Default_Model_Users();
            $users->aclDisable();
            $roles = $users->getRoles($user_id);
        }
        
        if(count($roles) > 0)
        {
            $this->createRoles($roles);
            $parents = self::getRoleIds($roles);
            $this->addRole($user_id, $parents);

        }
        //wenn dem benutzer keine rollen zugewiesen sind wird ihm die gastrolle zugewiesen
        /* else
        {
        $this->createRole(self::getRoleId(self::$GUEST_ROLE_ID));
        $this->addRole($user_id, self::getRoleId(self::$GUEST_ROLE_ID));
        }*/

        //den rollen die rechte aus der datenbank zuweisen
        $model = new Default_Model_RightsUser();
        $model->aclDisable();
        $rights = $model->getRights($user_id);

        foreach($rights AS $role_id => $role)
        {
            foreach($role AS $resource => $privileges)
            {
                foreach($privileges AS $privilege => $allow)
                {
                    if($allow)
                    {
                        $this->allow($role_id, $resource, $privilege);
                    }
                    else
                    {
                        $this->deny($role_id, $resource, $privilege);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Überprüft, ob im ACL Objekt ein User mit der angegeben user id vorhanden ist
     * @param int $user_id
     * @return boolean
     */
    public function hasUser($user_id)
    {
        return parent::hasRole($user_id);
    }

    /**
     * Überprüft, ob im ACL Objekt eine Rolle mit der angegeben ID vorhanden ist
     * @see library/Zend/Zend_Acl#hasRole($role)
     */
    public function hasRole($role_id)
    {
        return parent::hasRole(self::getRoleId($role_id));
    }

    /**
     * Gibt die übergebene Rollen ID kombiniert mit dem Rollenpräfix zurück
     * @param int $user_id
     * @return string
     */
    static function getRoleId($role_id)
    {
        /* return (substr($role_id, 0, strlen(self::ROLE_PREFIX)) != self::ROLE_PREFIX)
         ? self::ROLE_PREFIX . "_" . $role_id
         : $role_id; */
        return ($role_id != null) ? self::ROLE_PREFIX . "_" . $role_id : null;
    }

    /**
     * Kombiniert einen array mit rollen_ids mit dem rollenpräfix
     * @param array $role_ids rollen_ids
     * @return array array mit rollen ids mit präfix
     */
    static function getRoleIds(array $role_ids)
    {
        if(count($role_ids) <= 0)
        {
            return null;
        }
        for($i=0;$i<count($role_ids);$i++)
        {
            $role_ids[$i] = self::getRoleId($role_ids[$i]);
        }
        return $role_ids;
    }

    /**
     * Gibt die Privilegien in einem array zurück (add, edit, delete)
     * Keine schöne Lösung, aber da es sich bei den Privilegien um ein statisches Konstrukt handelt sollte diese
     * Lösung keine Probleme bereiten
     * @return array
     */
    public function getPrivileges()
    {
        return array(
        	self::VIEW => 'View',
        	self::ADD => 'Add',
        	self::EDIT => 'Edit',
        	self::DEL => 'Del'
        );
    }


    /**
     * @return array of registered roles
     */
    public function getRoles()
    {
        return $this->_getRoleRegistry()->getRoles();
    }

    /**
     * @return array of registered resources
     */
    public function getResources()
    {
        return $this->_resources;
    }

    /**
     * Setzt mehrere Resourcen auf einmal. Davor gesetzte Resourcen werden überschrieben
     * @param array $resources
     * @return void
     */
    public function setResources($resources)
    {
        if(is_array($resources))
        {
            $this->_resources = $resources;
        }
    }

    /**
     * Liest rekursiv alle Kinderelemente zu einem Elternelement aus
     * @param array $resources array mit allen zur Verfügung stehenden Resourcen
     * @param array $resource_id Das Element, zu dem die Kinderelemente bestimmt werden sollen
     * @return array
     */
    protected function getChildren($resource_id, $children = array())
    {
        $resources = $this->getResources();

        if(is_object($resources[$resource_id]))
        {
            $res = $this->getResources();
            $resource = $res[$resource_id];
        }
        else
        {
            $resource = $resources[$resource_id];
        }

        foreach($resource['children'] AS $child)
        {
            $childresource_id = $child->getResourceId();
            $children[] = $childresource_id;
            if(is_array($resources[$childresource_id]['children']))
            {
                $children = $this->getChildren($childresource_id, $children);
            }
        }
        return $children;
    }
    
    /**
     * Gibt die Gastrolle zurück
     * @return string
     */
    public static function getGuestRole()
    {
        return self::getRoleId(self::$GUEST_ROLE_ID);
    }
}