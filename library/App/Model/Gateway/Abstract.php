<?php
/**
 * Abstrakte Gateway Klasse. Die Gateway Klasse fungiert als "Vermittler" zwischen den Value Objekten und 
 * den ResultSet Objekten. In den konkreten Gateway Klassen werden Methoden für die spezifischeren Abfragen
 * erstelllt.
 *
 * @category   App
 * @package    App_Model
 * @subpackage Gateway
 * @author     kastners
 *
 */

class App_Model_Gateway_Abstract
{   
    /**
     * Name der Klasse, die Standardmäßig für die im Gateway erzeugten Value Objekte verwendet wird
     * @var string
     */
    protected $_valueClass = null;
    
    /**
     * Name der Klasse, die standardmäßig für die im Gateway erzeugten ResultSets erzeugt wird
     * @var string
     */
    protected $_resultClass = null;
    
    /**
     * Name der Klasse, von der standardmäßig die Tabellen Objekte abgeleitet werden.
     * @var string
     */
    protected $_tableClass = null;
    
    /**
     * Bestimmt ob für das Auslesen bzw das Erstellen der Datensätze ein Paginator verwendet werden soll, 
     * also ob die Datensätze Seitenweise oder alle auf einmal angezeigt werden sollen
     * @var bool
     */
    protected $_usePaginator = true;

    /**
     * Definiert den Standardwert an Datensätzen, die pro Seite angezeigt werden. Kann sowohl in den konkreten
     * Gateway Klassen als auch beim Auslesen der Datensätze geändert bzw angegeben werden
     * @var int
     */
    protected $_perpage = 10;
    
    /**
     * Speichert die aktuelle Seite. Wird für die Seitenweise Anzeige von Daten benötigt
     * @var int
     */
    protected $_page = null;
    
    /**
     * Der get Parameter, in dem die Seite gespeichert wird
     * @var string
     */
    protected $_pageparam = 'page';
    
    /**
     * ACL Objekt, das für die Rechteprüfungen verwendet wird
     * @var App_Acl
     */
    protected $_acl = null;
    
    /**
     * ID der ACL Rolle, die dem aktuellen Benutzer zugeordnet ist
     * @var string
     */
    protected $_aclRole = null;
    
    /**
     * Wenn true gibt wird der nächste ACL Check ignoriert und gibt in jedem Fall true zurück
     * @var boolean
     */
    protected $_aclEnabled = true;
    
    /**
     * Identität des aktuell eingeloggten Benutzers
     * @var array
     */
    protected $_identity = null;
    
    /**
     * Name der Standard Resource, die mit dem Gateway verknüpft ist. Muss in den erbenden Gateways gesetzt werden
     * @var string
     */
    protected $_resource = null;
    
    /**
     * Speicherung verschiedener Tabellen Objekte
     * @var array
     */
    protected $_tables = array();
    
    /**
     * Speichert das Zend_Paginator Objekt, das in fetchAll() erzeugt wurde. Es wird jeweils ein Paginator 
     * Objekt gespeichert. Wird die Methode mehrmals aufgerufen wird das vorher gespeicherte Objekt überschrieben
     * @var Zend_Paginator
     */
    protected $_paginator = null;
    
    /**
     * Konstruktor. Setzt die Optionen, falls ein Array mit optionen übergeben wird
     * @param array $options
     * @return void
     */
    public function __construct(array $options = null)
    {
        if($options != null)
        {
            $this->setOptions($options);
        }
    }
    
    /**
     * Liefert das angeforderte DAO. Wenn keine Parameter übergeben werden, dann wird das in der 
     * konkreten Model Klasse angegebene Default DAO geladen. Wird ein Objekt vom Typ Zend_Db_Table_Abstract übergeben,
     * dann wird dieses Objekt direkt weiterverarbeitet
     * @param string $tableClass Name der Tabellen Klasse
     * @return Zend_Db_Table_Abstract
     * @throws App_Model_Exception
     */
    public function getTable($tableClass = null)
    {
        if($tableClass == null)
        {
            if($this->_tableClass == null)
            {
                $valueClass = $this->getValueClass();
                $value = new $valueClass(null, array('gateway' => $this));
                $tableClass = $value->getTableClass();
                unset($value);
            }
            else
            {
                $tableClass = $this->_tableClass;
            }
        }
        //wenn ein objekt vom Typ Zend_Db_Table_Abstract übergeben wurde wird dieses direkt weiterverarbeitet
        elseif($tableClass instanceof Zend_Db_Table_Abstract)
        {
            $tableClassLower = strtolower(get_class($tableClass));
            if(!array_key_exists($tableClassLower, $this->_tables))
            {
                $this->_tables[$tableClassLower] = $tableClass;
            }
            return $this->_tables[$tableClassLower];
        }
        
        $tableClassLower = strtolower($tableClass);
        
        //überprüfung, ob von von der angegeben Klasse bereites ein Objekt erzeugt wurde
        if(array_key_exists($tableClassLower, $this->_tables))
        {
            return $this->_tables[$tableClassLower];
        }
        
        try {
            $this->_tables[$tableClassLower] = new $tableClass;
        }
        catch (Exception $e)
        {
            throw new App_Model_Exception("Das Tabellenobjekt konnte nicht erzeugt werden, da die Klasse
                                           daf&uuml;r nicht geladen werden konnte!<br>
                                           Klassenname: <i>".$tableClass."</i><br/>
                                           Das Objekt konnte nicht erzeugt werden weil: <br/>".$e->getMessage());
        }
        
        return $this->_tables[$tableClassLower];
    }
    
    /**
     * Gibt den Namen der Tabelle zurück. Als Parameter kann ein String mit dem Namen des Zend_Db_Table_Abstract Objekts
     * oder direkt ein Zend_Db_Table_Abstract Objekt übergeben werden. Wird kein Parameter übergeben wird der Name der 
     * im Gateway als Default definierten Tabelle zurückgegeben. 
     * @param String|Zend_Db_Table_Abstract $tableClass Zend_Db_Table_Abstract Objekt oder Name der Klasse 
     * @return String
     */
    public function getTableName($tableClass = null)
    {
        $table = $this->getTable($tableClass);
        return $table->info('name');
    }
    
    /**
     * Erstellt ein Value Objekt anhand der ID. Hierfür wird zuerst ein leeres Value Objekt erzeugt. 
     * Über das im Value Objekt spezifizierte Tabellen Objekt wird der zur ID gehörende Datensatz ausgelesen 
     * und im Value Objekt gespeichert.
     * @param int $id
     * @return App_Model_Value_Abstract
     */
    public function fetch($id)
    {
        $this->checkAcl(App_Acl::VIEW);
        return $this->getValue($id);
    }
    
    /**
     * Löscht den Datensatz mit der angegebenen ID. Alternativ zur ID kann auch ein String mit einem WHERE Statement
     * übergeben werden
     * @param int|string $id
     * @return anzahl der gelöschten Datensätze
     */
    public function delete($id)
    {
        $this->checkAcl(App_Acl::DEL);
        if(is_numeric($id))
        {
            $id = $this->getTable()->getAdapter()->quote($id, Zend_Db::INT_TYPE);
            $where = $this->getValue()->getPrimary(). ' = '.$id;
        }
        elseif(is_string($id))
        {
            $where = $this->getTable()->getAdapter()->quote($id);
        }
        return $this->getTable()->delete($where);
    }
    
    /**
     * Erstellt ein Value Objekt anhand der angegebenen WHERE Klausel aus der Default Tabelle des Value Objekts
     * @param string $where WHERE Klausel
     * @return App_Model_Value_Abstract
     */
    public function fetchWhere($where)
    {
        $this->checkAcl(App_Acl::VIEW);
        return $this->getValue($where);
    }
    
    /**
     * Holt alle Einträge aus der Default Tabelle
     * @param string|array|Zend_Db_Table_Select $where Where Klausel
     * @param string|array $order Order Klausel
     * @param int $count Anzahl der Datensätze, die ausgelesen werden sollen
     * @param int $offset Offset für den Query
     * @return App_Model_Resultset_Abstract
     * @see Zend_Db_Table_Abstract::fetchAll()
     */
    public function fetchAll($where = null, $order = null, $perpage = null, $offset = null)
    {
        $this->checkAcl(App_Acl::VIEW);
        $table = $this->getTable();
        if($this->_usePaginator == true)
        {
            $perpage = ($perpage == null) ? $this->_perpage : $perpage;
            $offset = ($offset == null) ? (($this->getPage()-1) * $perpage) : $offset;
            $this->setPaginator($perpage, $offset, $where);
        }
        
        $data = $table->fetchAll($where, $order, $perpage, $offset);
        
        return $this->createResultset($data);
    }
    
    /**
     * Ändert den Aktivierungsstatus des Datensatzes mit der übergebenen ID
     * @param int $id
     * @return App_Model_Gateway_Abstract
     */
    public function changeActiveState($id)
    {
        $this->checkAcl(App_Acl::EDIT);
        $val = $this->getValue();
        $activeCol = $val->getActiveCol();
        $primary = $val->getPrimary();
        unset($val);
        $data = array($activeCol => new Zend_Db_Expr("(".$activeCol." - 1)*(-1)"));
        $where = $primary. " = ".$this->getTable()->getAdapter()->quote($id, Zend_Db::INT_TYPE);
        $this->getTable()->update($data, $where);
        
        return $this;
    }
    
    /**
     * Erzeugt aus einem array ein gültiges Resultset
     * @param stdObject|array $results
     * @return App_Model_Resultset_Abstract
     */
    public function createResultset($results)
    {
        if(!is_array($results))
        {
            if(is_object($results) && method_exists($results, 'toArray'))
            {
                $results = $results->toArray();
            }
            else
            {
                throw new App_Model_Exception("Es konnte kein Resultset erzeugt werden, da keine gültigen 
                                               Daten (Array oder Objekt mit toArray Methode) übergeben wurden");
            }
        }
        $resultClass = $this->getResultClass();
        
        if(class_exists($resultClass))
        {
            return new $resultClass($results, $this);
        }
        else
        {
            $valueClass = $this->getValueClass();
            return new App_Model_Resultset_Resultset($results, $this, $valueClass);
        }
    }
    
    /**
     * Gibt die Anzahl der Datensätze in der Tabelle zurück. Es kann eine Optionale WHERE Klausel angegeben werden.
     * Es kann auch ein array mit mehreren WHERE Klauseln angegeben werden. Diese werden mi AND zu einem boolschen Ausdruck
     * verknüpft
     * @param string|array $where WHERE Klausel (opt)
     * @return int
     */
    public function getNumRows($where = null)
    {
        $this->checkAcl(App_Acl::VIEW);
        $select = $this->getTable()->select();
        $select->from($this->getTable(), array('num_rows' => new Zend_Db_Expr('COUNT(*)')))
               ->limit(1);
        if($where != null)
        {
            if(is_array($where))
            {
                foreach($where AS $w)
                {
                    $select->where($w);
                }
            }
            else
            {
                $select->where($where);
            }
        }
        
        $result = $select->query();
        $row = $result->fetch();
        return $row['num_rows'];
    }
    
    /**
     * Setzt die Optionen
     * @param array|Zend_Config $options
     * @return void
     */
    public function setOptions($options)
    {
        if ($options == null) 
        {
            return $this;
        }
 
        if ($options instanceof Zend_Config) 
        {
            $options = $options->toArray();
        } 
        elseif (is_object($options)) 
        {
            $options = (array) $options;
        }
 
        if (!is_array($options)) 
        {
            throw new App_Model_Exception('Ung&uuml;ltige Optionen &uuml;bergene! Es muss ein Array oder ein Objekt &uuml;bergeben werden!');
        }
 
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) 
        {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) 
            {
                $this->$method($value);
            }
        }
        return $this;    
    }
    
    /**
     * Überprüft, ob der aktuelle Benutzer die Rechte für eine Aktion hat
     * @param $privilege
     * @return bool
     */
    public function checkAcl($privilege, $resource = null)
    {
        $resource = ($resource == null) ? ($this->getResource()) : $resource;
        if(!$this->aclEnabled())
        {
            $this->aclEnable();
            return true;
        }
        if(!$this->getAcl()->isAllowed($this->getAclRole(), $resource, $privilege))
        {
            throw new App_Acl_Exception();
        }
        return true;
    }
    
    /**
     * Gibt die dem aktuellen Benutzer zugeordnete ACL Rolle zurück.
     * Wenn der Benutzer eingeloggt ist:
     *      -> rückgabe der benutzer id, unter der der benutzer im acl objekt abgelegt ist
     * Wenn der Benutzer nicht eingeloggt ist:
     *      -> rückgabe der gastrolle
     * @return string
     */
    public function getAclRole()
    {
        if($this->_aclRole == null)
        {
            $this->setAclRole();
        }
        return $this->_aclRole;
    }
    
    /**
     * Setzt den Namen der ACL Rolle, die den aktuell eingeloggten Benutzer identifiziert
     * @param string $role (opt) Wenn nicht gesetzt wird die Benutzerrolle automatisch bestimmt
     * @return App_Model_Gateway_Abstract
     */
    public function setAclRole($role = null)
    {
        if($role == null)
        {
            $identity = $this->getIdentity();
            if($identity)
            {
                $role = $identity['user_id'];
            }
            else
            {
                $role = App_Acl::getRoleId(App_Acl::$GUEST_ROLE_ID);
            }
        }
        $this->_aclRole = $role;
    }
    
    /**
     * Gibt das ACL Objekt zurück
     * @return App_Acl
     */
    public function getAcl()
    {
        if($this->_acl == null)
        {
            $this->_acl = Zend_Registry::get('acl');
        }
        return $this->_acl;
    }
    
    /**
     * Stellt die ACL Überprüfung für den nächsten Aufruf ab.
     * @return App_Model_Gateway_Abstract
     */
    public function aclEnable()
    {
        $this->_aclEnabled = true;
        return $this;
    }
    
    /**
     * Deaktiviert die ACL Überprüfung für die nächste ACL Überprüfung! 
     * Die Überprüfung wird nur für die NÄCHSTE ACL Abfrage auf GENAU DIESEM Gateway Objekt ausgesetzt!
     * @return App_Model_Gateway_Abstract
     */
    public function aclDisable()
    {
        $this->_aclEnabled = false;
        return $this;
    }
    
    /**
     * Gibt zurück ob die Überprüfung der Zugriffsrechte an oder abgestellt ist
     * @return boolean
     */
    public function aclEnabled()
    {
        return $this->_aclEnabled;
    }
    
    /**
     * gibt den Namen der standard resource zurück, mit der das gateway verknüpft ist
     * @return string name der resource
     */
    public function getResource()
    {
        return $this->_resource;
    }
    
    /**
     * Setzt den Namen der Standard Resource, die mit dem Gateway verknüpft ist
     * @param $resource
     * @return App_Model_Value_Abstract
     */
    public function setResource($resource)
    {
        $this->_resource = $resource;
        return $this;
    }
    
    /**
     * Gibt die Identität des aktuell eingeloggten Benutzers zurück
     * @return array identity
     */
    public function getIdentity()
    {
        if($this->_identity == null)
        {
            $this->setIdentity();
        }
        return $this->_identity;
    }
    
    /**
     * Setzt die Identity des aktuell eingeloggten Benutzers
     * @param $identity
     * @return App_Model_Gateway_Abstract
     */
    public function setIdentity($identity = null)
    {
        if($identity == null)
        {
            $auth = App_Auth::getInstance();
            if($auth->hasIdentity())
            {
                $identity = $auth->getIdentity();
            }
            else
            {
                $identity = false;
            }
        }
        $this->_identity = $identity;
        return $this;
    }
    
    /**
     * Erstellt ein neues Value Objekt. Wird keine valueClass angegeben wird die in der Klasse 
     * definierte Standard _valueClass geladen
     * @param int|string|stdObject|array $data die Daten für das Value Objekt. 
     *                                   Folgende Werte sind möglich: 
     *                                          int -> wird als ID des Datensatzes interpretiert
     *                                          string -> wird als WHERE Klausel interpretiert
     *                                          stdObject -> Versuch, das Objekt in einen Array umzuwandeln
     *                                          array -> Daten aus dem Array werden direkt in das Value Objekt geschrieben
     * @param $valueClass
     * @return App_Model_Value_Abstract
     */
    public function getValue($id = null, $valueClass = null)
    {
        //wenn die valueClass nicht explizit angegeben wurde wird diese mit getValueClass() ermittelt
        if($valueClass == null)
        {
            $valueClass = $this->getValueClass();
        }
        
        //es wird versucht ein neues Value Objekt zu erzeugen
        /* try {
            $valueObject = new $valueClass($id, array('gateway' => $this));
        }
        catch(Exception $e)
        {
            throw new App_Model_Exception("Es konnte kein Value Objekt vom Typ <i>".$valueClass."</i> erzeugt werden");
        } */
        $valueObject = new $valueClass($id, array('gateway' => $this));
        
        return $valueObject;
    }
    
    /**
     * Gibt den Namen der Klasse für die Value Objekte zurück. Zuerst wird überprüft, ob in dem Gateway Objekt
     * ein Name für die Value Klasse angegeben wurde. Wenn nicht, dann wird der Name der Value Klasse über 
     * Konventionen erstellt. 
     * Nach der Konvention befinden sich die Value Klassen im gleichen Modul im Ordner "models/values". Der
     * Name der Value Klasse ist in der Einzahl. 
     * Ist der Name der Gateway Klasse Beispielsweise Default_Model_Roles, dann wäre der Name für die Value Klasse
     * nach Konvention Admin_Model_Value_Role
     * @param string $type
     * @return string
     * @see getDefaultClass()
     */
    public function getValueClass()
    {
        if($this->_valueClass != null)
        {
            return $this->_valueClass;
        }
        
        $valueClass = $this->getDefaultClass('value');
        $this->setValueClass($valueClass);
        return $valueClass;
    }
    
    /**
     * Setzt den Namen der Klasse, die für die Value Objekte verwendet wird
     * @param string $valueClass
     * @return App_Model_Gateway_Abstract
     */
    public function setValueClass($valueClass)
    {
        $this->_valueClass = $valueClass;
        return $this;
    }
    
    /**
     * Gibt den Namen der Klasse für die erzeugten ResultSets zurück
     * @return string
     * @see getDefaultClass()
     */
    public function getResultClass()
    {
        if($this->_resultClass != null)
        {
            return $this->_resultClass;
        }
        
        $resultClass = $this->getDefaultClass('resultset');
        $this->setResultClass($resultClass);
        
        return $this->_resultClass;
    }
    
    /**
     * Setzt den Wert von resultClass (Name der Klasse, die für die Resultsets verwendet wird)
     * @param string $resultClass
     * @return App_Model_Gateway_Abstract
     */
    public function setResultClass($resultClass)
    {
        $this->_resultClass = $resultClass;
        return $this;
    }
    
    /**
     * Gibt den Namen der Klasse für die Value Objekte zurück. Zuerst wird überprüft, ob in dem Gateway Objekt
     * ein Name für die Value Klasse angegeben wurde. Wenn nicht, dann wird der Name der Value Klasse über 
     * Konventionen erstellt. 
     * Erstellt anhand des Namens der eigenen Klasse den Default Namen für die Klassen für die Value bzw
     * die Resultset Klassen. Die Konventionen hierfür sind:
     * Die Value Klassen im gleichen Modul im Ordner "models/values". Der Name der Value Klasse ist
     * in der Einzahl. Die Resultset Klassen werden ebenfalls im gleichen Modul, aber im Ordner 
     * "models/resultset" gesucht. Die Namen für die Resultset Klassen sind in der Mehrzahl.
     * 
     * Ist der Name der Gateway Klasse Beispielsweise Default_Model_Roles, dann wäre der Name für die
     * Value Klasse Admin_Model_Value_Role und die für das Resultset Admin_Model_Resultset_Roles
     * @param string $type Erlaubt sind value und resultset! Bei ungültigem wert wird eine Exception geworfen
     * @return string
     * @throws App_Model_Exception
     */
    protected function getDefaultClass($type)
    {        
        $gatewayClass = get_class($this);
        $gatewayLength = strlen($gatewayClass);
        
        if($type == 'value')
        {
            $insertion = 'Value_';
            if($gatewayClass{($gatewayLength - 1)} == 's')
            {
                $gatewayClass = substr($gatewayClass, 0, ($gatewayLength-1));
            }
        }
        elseif($type == 'resultset')
        {
            $insertion = 'Resultset_';
        }
        else
        {
            throw new App_Model_Exception("Ung&uuml;ltiger Klassen Typ angegeben! Erlaubt sind value und resultset!");
        }
        
        $lastUnderscore = strrpos($gatewayClass, '_') + 1;
        $class = substr($gatewayClass, 0, ($lastUnderscore)) . $insertion . substr($gatewayClass, ($lastUnderscore));
        
        return $class;
    }
    
    /**
     * Setzt die aktuelle Seite. Wenn kein Wert übergeben wurde, dann wird der Parameter 'page' aus dem
     * Request Objekt ausgelesen und als aktuelle Seite gesetzt. Wenn dieser Parameter nicht vorhanden ist,
     * dann wird die aktuelle Seite standardmäßig auf 1 gesetzt. 
     * @param int $page Wert, auf den die Seite gesetzt werden soll OPTIONAL 
     * @return App_Model_Gateway_Abstract
     */
    public function setPage($page = null)
    {
        if($page == null)
        {
            $request = Zend_Controller_Front::getInstance()->getRequest();
            $page = $request->getParam($this->_pageparam, 1);
        }
        if(!is_numeric($page))
        {
            $page = 1;
        }
        $this->_page = $page;
        
        return $this;
    }
    
    /**
     * Gibt die aktuelle Seite zurück. Wenn die Seite noch nicht gesetzt wurde wird setPage() aufgerufen
     * @return int aktuelle Seite
     */
    public function getPage()
    {
        if($this->_page == null)
        {
            $this->setPage();
        }
        return $this->_page;
    }
    
    /**
     * Gibt den Paginator zurück
     * @return Zend_Paginator
     */
    public function getPaginator()
    {        
        return $this->_paginator;
    }
    
    /**
     * Speichert das Paginator Objekt. Diese Methode wird jeweils nach dem Aufruf von fetchAll() aufgerufen
     * und speichert somit jeweils das letzte Paginator Objekt, das bei einem Aufruf von fetchAll() erzeugt
     * wurde
     * @param string $where optionale where klausel
     * @param int $perpage optionale perpage angabe
     * @param int $offset optionale offset angabe
     * @return App_Model_Gateway_Abstract
     */
    public function setPaginator($perpage, $offset, $where = null)
    {
        $table = $this->getTable();
        $select = $table->select()
                        ->from($table, array('COUNT(*) AS num_rows'));
        if($where != null)
        {
            $select->where($where);
        }
        $result = $table->fetchRow($select)->toArray();
        $num_rows = (int) $result['num_rows'];

        $paginator = Zend_Paginator::factory($num_rows);
        $paginator->setItemCountPerPage($perpage);
        $paginator->setCurrentPageNumber($this->getPage());
            
        $this->_paginator = $paginator;
        
        return $this;
    }
    
    /**
     * Legt fest, ob die Datensätze bei der Verwendung von fetchAll() seitenweise ausgelesen werden sollen oder nicht
     * @param bool $flag
     * @return App_Model_Gateway_Abstract
     */
    public function setUsePaginator($flag)
    {
        $this->_usePaginator = (bool) $flag;
    }
}