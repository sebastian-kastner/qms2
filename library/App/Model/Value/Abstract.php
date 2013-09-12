<?php
/**
 * 
 * 
 * Abstrakte Klasse für die Value Objekte. Die Value Objekte repräsentieren jeweils einen einzigen Datensatz
 * aus der Datenbank. 
 * In den konkreten Klassen, die von der abstrakten Value Klasse erben müssen in dem array $_allowed die 
 * erlaubten Felder spezifiziert werden (in der Regel die Felder der Datenbank)
 * Außerdem muss der Name des Tabellenobjektes (vom Typ Zend_Db_Table_Abstract) und der Name der Zend_Form
 * Klasse, die als Validator fungieren soll angegeben werden.
 * Die Abstrakte Value Klasse liefert von Haus aus Funktionen um die einzelnen Datensätze zu speichern und zu 
 * löschen sowie einige getter und setter Methoden. 
 * 
 * @category   App
 * @package    App_Model
 * @subpackage Value
 * @author     kastners
 */
class App_Model_Value_Abstract
{
    /**
     * @var Array Erlaubte properties
     */
    protected $_allowed = array();
    
    /**
     * @var Array Properties, die bei Insert und Update Operationen nicht erlaubt sind (zB Primarys)
     */
    protected $_protected = array();
    
    /**
     * @var String Klassenname des Standardformulars
     */
    protected $_formClass = null;
    
    /**
     * @var String Klassenname der Standardtabelle
     */
    protected $_tableClass = null;
    
    /**
     * @var Array Eigentlichen Daten des Value Objekts
     */
    protected $_data = array();
    
    /**
     * @var Zend_Form Zum Value Objekt gehörendes Formular
     */
    protected $_form = null;
    
    /**
     * @var App_Model_Gateway_Abstract Zum Value Objekt gehörendes Gateway
     */
    protected $_gateway = null;
    
    /**
     * @var Zend_Db_Table_Abstract Zum Value Objekt gehörendes Tabellenobjekt
     */
    protected $_table = null;
    
    /**
     * @var Primary der zum Value Objekt gehörenden Tabelle
     */
    protected $_id = null;
    
    /**
     * Die letzte durchgeführte aktion. Wird nach update, insert oder update Operationen geändert
     * @var string enthält mögliche werte: insert, update, delete
     */
    protected $_lastAction = null;
    
    /**
     * Der Name der Entität (wird bei statusmeldungen angezeigt)
     * @var string
     */
    protected $_entityName = null;
    
    /**
     * Spalte, in der das erstellungsdatum gespeichert wird
     * @var string
     */
    protected $_dateCreated = 'date_created';
    
    /**
     * spalte, in der der Ersteller gespeichert wird
     * @var string
     */
    protected $_createdBy = 'created_by';
    
    /**
     * Spalte, in das update datum gespeichert wird
     * @var string
     */
    protected $_dateUpdated = 'date_updated';
    
    /**
     * Spalte, in der der User gespeichert wird, der die letzten Änderungen vorgenommen hat
     * @var string
     */
    protected $_updatedBy = 'updated_by';
    
    /**
     * Spalte, in der der Aktivierungsstatus gespeichert wird
     * @var string
     */
    protected $_active = 'is_active';
    
    /**
     * Konstruktor. Überprüft, ob der Array für die erlaubten Felder gesetzt ist und füllt das Objekt 
     * anschließend mit den übergebenen Daten.
     * 
     * Wird ein Array übergeben, dann werden die Daten aus dem Array über die populate Methode mit den 
     * im Value Objekt erlaubten Feldern abgeglichen und in das Value Objekt geschrieben.
     * 
     * Wird eine Zahl übergeben wird diese als ID interpretiert. Anhand der ID wird ein Datensatz ausgelesen
     * und die Daten des Resultats werden in das Value Objekt geschrieben. 
     * @param int|string|stdObject|array $data die Daten für das Value Objekt. 
     *                                   Folgende Werte sind möglich: 
     *                                          int -> wird als ID des Datensatzes interpretiert
     *                                          string -> wird als WHERE Klausel interpretiert
     *                                          stdObject -> Versuch, das Objekt in einen Array umzuwandeln
     *                                          array -> Daten aus dem Array werden direkt in das Value Objekt geschrieben
     * @return void
     * @throws App_Model_Exception
     */
    public function __construct($data = null, $options = null)
    {
        $this->setOptions($options);
        
        if($this->getGateway() == null)
        {
            throw new App_Model_Exception('Kein Gateway angegeben!');    
        }
        
        //wenn keine erlaubten felder angegeben wurden werden die felder der tabelle als solche vom tabellenobjekt übernommen
        if(empty($this->_allowed))
        {
            $table = $this->getTable();
            $this->_allowed = $table->info('cols');
        }
        
        $table = $this->getTable();
            
        //wenn anstelle eines $data arrays eine Zahl übergeben wurde, wird diese Zahl als ID interpretiert und der 
        //Datensatz zu der ID aus der DB gelesen
        if(is_numeric($data))
        {
            $id = $table->getAdapter()->quote($data, Zend_Db::INT_TYPE);
            $data = $table->find($id);
            $data = $data->toArray();
            if(count($data) == 0)
            {
                throw new App_Model_Exception("Zu der angegebenen ID konnte kein Datensatz gefunden werden");
            }
            $data = $data[0];
        }
        //wenn anstelle eines $data arrays ein String übergeben wurde wird dieser Sting als WHERE statement interpretiert
        elseif(is_string($data))
        {
            $select = $table->select();
            $data = $table->getAdapter()->quote($data);
            $select->from($this->getTableName())
                   ->where($data)
                   ->limit(1);
            $result = $select->query();
            $data = $result->fetchAll();
            if(count($data) != 0)
            {
                throw new App_Model_Exception("Es konnte kein Value Objekt erzeugt werden, da zu dem angegebenen
                              WHERE Statement kein oder zu viele Datensätze gefunden wurde!");
            }
            
        }
        elseif(is_object($data))
        {
            if(method_exists($data, 'toArray'))
            {
                $data = $data->toArray();
            }
            else
            {
                throw new App_Model_Exception('Beim Erzeugen des Value Objekts wurde ein ungültiger Wert übergeben!
                                               Es wurde ein Objekt übergeben, das nicht in einen Array umgewandelt werden kann!');
            }
        }
        //elseif(!is_array($data))
        //{
          //  throw new App_Model_Exception('Beim Erzeugen des Value Objekts wurde ein ungültiger Wert übergeben!');
        //}
        
        //TODO: integration des formulars beim erstellen des objekts bzw bei dessen "befüllung"
        $this->populate($data);
    }
    
    /**
     * Speichert das Value Objekt in der Datenbank (je nachdem ob das Value Objekt über eine ID
     * verfügt oder nicht wird ein neuer Datensatz angelegt oder ein existierender bearbeitet)
     * @return mixed|bool anzahl der bearbeiteten datensätze (update), id des eingefügten datensatz (insert) 
     *                    oder false, wenn das formular nicht validiert werden konnte
     */
    public function save()
    {
        $validator = $this->getForm();
        $idField = $this->getPrimary();
        
        if($validator->isValid($this->_data))
        {
            $table = $this->getTable();
            
            //ID ist nicht leer => UPDATE operation
            if(!empty($this->_data[$idField]))
            {
                return $this->update();
            }
            else
            {
                return $this->insert();
            }
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Bearbeiten des Datensatzes in der Datenbank
     * @return int Anzahl der bearbeiteten Datensätze (in der Regel 1)
     */
    protected function update()
    {
        $this->getGateway()->checkAcl(App_Acl::EDIT);
        $table = $this->getTable();
        $data = $this->stripUnallowedKeys();
        $data = $this->clearProtectedProperties($data);
        $idField = $this->getPrimary();
        if(is_numeric($this->_data[$idField]))
        {
            $id = $table->getAdapter()->quote($this->_data[$idField], Zend_Db::INT_TYPE);
        }
        else
        {
            $id = $table->getAdapter()->quote($this->_data[$idField]);
        }
        
        if(in_array($this->_dateUpdated, $this->_allowed))
        {
            $data[$this->_dateUpdated] = time();
        }
        $update = $table->update($data, $idField." = ".$id);
        
        //TODO: um created_by (user_id) erweitern
        $this->setLastAction('update');
        return $update;
    }
    
    /**
     * Einfügen des Value Objektes als neue Zeile ein der Datenbank
     * @param bool $insert_id Gibt an, ob die ID des Datensatzes im Insert Statement eingefügt werden 
     *                        soll oder nicht. Bei Autoincrement wird die ID Standardmäßig nicht 
     *                        eingefügt.
     * @return mixed Primärschlüssel des eingefügten Datensatzes
     */
    protected function insert($insert_id = false)
    {
        $this->getGateway()->checkAcl(App_Acl::ADD);
        $table = $this->getTable();
        $data = $this->stripUnallowedKeys();
        $strip_id = ($insert_id) ? false : true;
        $data = $this->clearProtectedProperties($data, $strip_id);
        $data = $this->addAdditionalInsertData($data);
        
        $insert = $table->insert($data);
        
        //nach dem das objekt in die datenbank geschrieben wurde hat es in der datenbank eine id
        //diese id wird für eventuelle weitere operationen direkt im objekt gespeichert
        $this->_data[$this->getPrimary()] = $insert;
        $this->setLastAction('insert');
        return $insert;
    }
    
    /**
     * Erweitert einen Array mit Daten für einen Insert um etwaige Zusatzinformationen (created_by, created_on, etc..)
     * @param array $data array, an den die zusätzlichen Daten angehängt werden sollen
     * @return array
     */
    protected function addAdditionalInsertData(array $data)
    {
        if(in_array($this->_dateCreated, $this->_allowed))
        {
            $data[$this->_dateCreated] = time();
        }
        //TODO: created_by einfügen!
        return $data;
    }
    
    /**
     * Löscht das Value Objekt aus der Datenbank.
     * @return int Anzahl der gelöschten Datensätze
     * @throws App_Model_Exception
     */
    public function delete()
    {
        $this->getGateway()->checkAcl(App_Acl::DEL);
        $idField = $this->getPrimary();
        
        $id = $this->_data[$idField];
        
        if(empty($id))
        {
            throw new App_Model_Exception("Datensatz kann nicht gel&ouml;scht werden, da keine ID angegeben wurde!");    
        }
        
        $table = $this->getTable();
        $id = $table->getAdapter()->quote($id, Zend_Db::INT_TYPE);
        
        //TODO: soft delete implementieren! (falls date_deleted in db)
        
        $delete = $table->delete($idField." = ".$id);
        $this->setLastAction('delete');
        return $delete;
    }
    
    
    /**
     * Objekt mit Daten füllen
     * @param array|object $data Die Daten, mit denen das Objekt befüllt werden soll. 
     *        Objekte mit toArray Methode und Arrays erlaubt.
     *        Beim "befüllen" des Objekts wird noch nicht überprüft, ob die jeweiligen keys überhaupt im Objekt erlaubt sind.
     *        Dies geschieht erst, wenn das Objekt in die Datenbank geschrieben wird
     * @param bool $validate Sollen die Daten vor dem Schreiben ins Value Objekt über das Formular validiert werden? (opt)
     * @return App_Model_Value_Abstract
     * @throws App_Model_Exception
     */
    public function populate($data, $validate = true)
    {   
        //abbruch, wenn keine daten übergeben wurden
        if($data == null)
        {
            return $this;
        }
        
        //überprüfung, ob ein objekt übergeben wurde und ob dieses über eine toArray methode verfügt
        if (is_object($data) && method_exists($data, 'toArray')) 
        {
            $data = $data->toArray();
        }
        //falls keine toArray methode versuch eines casts
        elseif (is_object($data)) 
        {
            $data = (array) $data;
        }
        
        if(!is_array($data))
        {
            throw new App_Model_Exception('Ung&uuml;ltiger Wert &uuml;bergeben!');
        }
        //speicherung der übergebenen daten im objekt. hier wird noch nicht (!!) überprüft, ob die daten im 
        $this->_data = $data;
        
        return $this;
    }
    
    /**
     * Gibt den Wert zurück, der unter $key gespeichert ist
     * @param string $key
     * @return App_Model_Value_Abstract
     */
    public function getValue($key)
    {
        return $this->_data[$key];
    }
    
    /**
     * Setzt einen Wert in Value Objekt
     * @param string $key index des wertes
     * @param string $value wert, der gesetzt werden soll
     * @return App_Model_Value_Abstract
     */
    public function setValue($key, $value)
    {
        if(!in_array($key, $this->_allowed))
        {
            throw new App_Model_Exception('Der Wert "'.$key."' ist nicht erlaubt und kann deswegen nicht gesetzt werden!");
        }
        $this->_data[$key] = $value;
        return $this;
    }
    /**
     * Nimmt den Array mit den Objektdaten ($this->_data) und entfernt alle nicht erlaubten keys, um bei insert und update
     * operationen keine Fehlermeldungen zu erzeugen
     * Der $this->_data array wird dabei NICHT berührt! es wird ein neuer array ohne die nicht erlaubten keys erzeugt und 
     * zurückgegeben
     * alternativ kann auch ein array mit daten übergeben werden. Dann wird dieser array in bearbeitet und zurückgegeben
     * @param array $data (optional) daten array, der zu bearbeiten ist
     * @return array
     */
    public function stripUnallowedKeys(array $data = null)
    {
        $data = ($data == null) ? $this->_data : $data;
        $newData = array();
        //speicherung der übergebenen daten im objekt, falls die keys erlaubt sind
        foreach($data AS $key => $value)
        {
            if(in_array($key, $this->_allowed))
            {
                $newData[$key] = $value;
            }
            //unschöne lösung, um für den submit button von formularen keine notices zu erhalten
            else
            {
                //trigger_error("Der Key <i>".$key."</i> ist in ".get_class($this)." nicht erlaubt!", E_USER_NOTICE);
            }
        }
        
        return $newData;
    }
    
    /**
     * Konvertiert das Value Objekt in einen Array mit den Daten aus data
     * @return Array Array mit den Daten des Value Objekts
     */
    public function toArray()
    {
        $data = array();
        foreach ($this->_data as $key => $value) 
        {
            if ($value !== null) 
            {
                $data[$key] = $value;
            }
        }
        return $data;
    }
    
    /**
     * Gibt den Namen der Tabelle zurück. Wird kein Wert übergeben wird der Name der Tabelle zurückgegeben, die als 
     * Default Tabelle für das Value Objekt definiert ist. Ansonsten kann der Name einer Tabellenklasse übergeben werden
     * @param string $tableClass Name der Tabellenklasse (opt)
     * @return string
     */
    public function getTableName($tableClass = null)
    {
        if(!is_string($tableClass))
        {
            $tableClass = $this->getTableClass();
        }
        return $this->getGateway()->getTableName($tableClass);
    }
    
    /**
     * Gibt einen Array zurück, in dem Informationen über die zuletzt ausgeführte Aktion enthalten sind
     * Diese Methode dient hauptsächlich dazu auf einer Weiterleitungsseite Informationen zum 
     * bearbeiteten/gelöschten/eingetragenen Datensatz
     * Im Array, der zurückgegeben wird existieren folgende keys:
     *   msg => eine beschreibung der letzten aktion mit Informationen wie der Art der Aktion und der ID
     *   head => kurze überschrift zur aktion
     *   redirect => wenn eine adresse übergeben wird, an die weitergeleitet werden soll, wird diese hier gespeichert
     * @return array
     */
    //TODO: status meldungen auch für nicht geglückte operationen implementieren!
    public function getRedirectOptions($redirect = null)
    {
        $actionsMsg = array(
            'insert' => 'eingefügt',
            'update' => 'bearbeitet',
            'delete' => 'gelöscht'
                        );
        
        $actionsHead = array(
            'insert' => 'einfügen',
            'update' => 'bearbeiten',
            'delete' => 'löschen'
                        );

        $actionMsg = $actionsMsg[$this->getLastAction()];
        $actionHead = $actionsHead[$this->getLastAction()];
        
        $entity = $this->getEntityName();
        
        $head = $entity." ".$actionHead;
        $msg = $entity." (ID: ".$this->_data[$this->getPrimary()].") wurde erfolgreich ".$actionMsg."!";
        $redirectOptions = array('head' => $head, 'msg' => $msg);
        if($redirect != null)
        {
            $redirectOptions['redirect'] = $redirect;
        }
        return array('redirectOptions' => $redirectOptions);
    }
    
    /**
     * Gibt den Namen der Entität zurück
     * @return string
     */
    public function getEntityName()
    {
        if($this->_entityName != null)
        {
            return $this->_entityName;
        }
        $this->setEntityName();
        return $this->_entityName;
    }
    
    /**
     * Setzt den Namen der Entität. Wird kein Wert übergeben wird der Name der Entität aus dem Klassennamen
     * abgeleitet. Heißt die Klasse Beispielsweise Admin_Model_Value_Role, dann wird daraus der Entitätsname
     * Role abgeleitet
     * @param $entity
     * @return unknown_type
     */
    public function setEntityName($entity = null)
    {
        if($entity == null)
        {
            $className = get_class($this);
            $entity = substr($className, (strrpos($className, '_')+1));
        }
        $this->_entityName = $entity;
        return $this;
    }
    
    /**
     * Die init() Funktion ist ein Hook, der von den erbenden Klassen ausgefüllt werden kann.
     * Sie wird am Ende des Konstruktors aufgerufen und dient dazu, dass die Unterklassen bei der Erzeugung
     * eines Objektes eigenen Code hinzufügen können ohne die __construct() Methode überschreiben zu müssen
     * @return void
     */
    protected function init()
    {
    }
    
    /**
     * Setzt lastAction, also die zuletzt auf dem Objekt ausgeführte Aktion
     * @param string $action die zuletzt ausgeführt action (erlaubte werte: insert, update, delete)
     * @return App_Model_Value_Abstract
     * @throws App_Model_Exception
     */
    public function setLastAction($action)
    {
       if($action != 'update' && $action != 'insert' && $action != 'delete')
       {
           throw new App_Model_Exception("Fehler beim setzen der letzten durchgeführten Aktion! Erlaubte Werte sind update, insert und delete");
       }
       $this->_lastAction = $action;
       return $this;
    }
    
    /**
     * Gibt die Art der letzten durchgeführten Aktion zurück
     * @return string art der letzten durchgeführten Aktion
     */
    protected function getLastAction()
    {
        return $this->_lastAction;
    }
    
    /**
     * Löscht geschützte Properties aus einem Array, bevor dieser in die Datenbank geschrieben wird.
     * Die geschützten Properties werden in dem Array $_protected definiert. Auch wenn $_protected leer ist
     * wird zumindest das Primary Feld aus dem Daten Array gelöscht
     * Muss vor Insert oder Update Operationen aufgerufen werden!
     * @param array $data
     * @param bool $clear_id (opt) Gibt an, ob die ID entfernt werden soll oder nicht
     * @return array
     */
    protected function clearProtectedProperties(array $data, $clear_id = true)
    {
        if($clear_id && !array_key_exists($this->getPrimary(), $this->_protected))
        {
            $this->_protected[] = $this->getPrimary();
        }
        
        foreach($data AS $key => $value)
        {
            if(in_array($key, $this->_protected))
            {
                unset($data[$key]);
            }
        }
        return $data;
    }
    
    /**
     * Optionen setzen
     * @param array|Zend_Config $options Optionen
     * @return App_Model_Value_Abstract
     * @throws App_Model_Exception
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
     * Überprüft, ob im Value Objekt ein Primärschlüssel vorhanden ist, oder ob das Feld dafür leer ist
     * @return bool
     */
    protected function checkPrimary()
    {
        if(empty($this->_data[$this->getPrimary()]))
        {
            return false;
        }
        return true;
    }
    
    /**
     * Gibt das Tabellenobjekt zurück (falls es noch nicht erstellt wurde wird es vom gateway angefordert)
     * @return Zend_Db_Table_Abstract
     */
    public function getTable()
    {
        if($this->_table == null)
        {
            $tableClass = $this->getTableClass();
            
            $this->_table = $this->getGateway()->getTable($tableClass);
            $this->setPrimary();
        }
        return $this->_table;
    }
    
    /**
     * Setzt das Tabellenobjekt
     * @param $table Zend_Db_Table_Abstract das tabellenobjekt, das für das value objekt gesetzt werden soll
     * @return Zend_Db_Table_Abstract
     */
    public function setTable(Zend_Db_Table_Abstract $table)
    {
        $this->_table = $table;
        $this->setPrimary();
        return $table;
    }
    
    /**
     * Gibt den Namen der Klasse für die Tabellen Objekte zurück. Zuerst wird überprüft, ob in dem Value Objekt
     * ein Name für die Tabellen Klasse angegeben wurde. Wenn nicht, dann wird der Name der Tabellen Klasse über 
     * Konventionen erstellt. 
     * Nach der Konvention befinden sich die Tabellen Klassen im gleichen Modul im Ordner "models/DbTable". Der
     * Name der Tabellen Klasse ist in der Mehrzahl.
     * Ist der Name der Value Klasse beispielsweise Admin_Model_Value_Role, dann wäre der Name für die
     * Tabellen Klasse ohne weitere Angaben Admin_Model_DbTable_Roles
     * @return string
     */
    public function getTableClass()
    {
        if($this->_tableClass != null)
        {
            return $this->_tableClass;
        }
        
        $valueClass = get_class($this);
        $tableClass = str_replace('Value', 'DbTable', $valueClass);
        
        if($tableClass{(strlen($tableClass)-1)} != 's')
        {
            $tableClass = $tableClass . "s";
        }
        
        $this->setTableClass($tableClass);
        return $tableClass;
    }
    
    /**
     * Setzt den Namen der Klasse für die Tabellen Objekte
     * @param string $tableClass
     * @return App_Model_Value_Abstract
     */
    public function setTableClass($tableClass)
    {
        $this->_tableClass = $tableClass;
        return $this;
    }
    
    /**
     * Gibt den Namen des Felds mit dem Primärschlüssel zurück. Falls die ID noch nicht ausgelesen wurde
     * liest die Methode den Namen des ID Feldes von dem Tabellen Objekt aus
     * @return string Name des Primary Feldes
     */
    public function getPrimary()
    {
        if($this->_id == null)
        {
            $this->setPrimary();
        }
        return $this->_id;
    }
    
    /**
     * Setzt den Namen des Primary Feldes. Der Name wird vom im Objekt gespeicherten Tabellen Objekt ausgelesen
     * @return App_Model_Value_Abstract
     */
    public function setPrimary()
    {
        $info = $this->getTable()->info('primary');
        $this->_id = $info[1];
        return $this;
    }
    
    /**
     * Gibt den Wert des Primärschlüssels zurück. Wenn das Feld für den Primärschlüssel leer ist wird eine Exception geworfen
     * @return int
     * @throws App_Model_Exception
     */
    public function getPrimaryValue()
    {
       if(empty($this->_data[$this->getPrimary()]))
       {
           throw new App_Model_Exception('Die Operation konnte nicht durchgeführt werden, da das Value Objekt nicht eindeutig
                                          definiert ist, da keine ID vorhanden ist');
       }
       
       return $this->_data[$this->getPrimary()];
    }
    
    
    /**
     * Gibt das gateway zurück
     * @return App_Model_Gateway_Abstract
     */
    public function getGateway()
    {       
        return $this->_gateway;
    }
    
    /**
     * Setzt das Gateway des Value Objekts
     * @param $gateway App_Model_Gateway_Abstract Das Gateway Objekt, das gesetzt werden soll
     * @return App_Model_Gateway_Abstract
     */
    public function setGateway(App_Model_Gateway_Abstract $gateway)
    {
        $this->_gateway = $gateway;
        return $gateway;
    }
    
    /**
     * Gibt den Namen der Spalte zurück, in der der Aktivierungsstatus gespeichert wird
     * @return string
     */
    public function getActiveCol()
    {
        return $this->_active;
    }
    
    /**
     * Gibt das zum Value Objekt gehörende Formular Objekt zurück. 
     * Falls das Formular Objekt leer ist wird ein neues Formular erzeugt 
     * @return Zend_Form
     */
    public function getForm($data = null)
    {
        $this->getGateway()->checkAcl(App_Acl::VIEW);
        if($this->_form == null)
        {
            $this->setForm();
        }
        
        return $this->_form;
    }
    
    /**
     * Setzt das Formular Objekt
     * @param Zend_Form $form
     * @return Zend_Form
     * @throws App_Model_Exception
     */
    public function setForm(Zend_Form $form = null)
    {
        if(!is_a($form, "Zend_Form"))
        {
            $formClass = $this->getFormClass();
            $this->_form = new $formClass($this->toArray(), null, $this);
        }
        else
        {
            $this->_form = $form;
        }
        return $this->_form;
    }
    
    /**
     * Gibt den Namen der Klasse für die Formular Objekte zurück. Zuerst wird überprüft, ob in dem Value Objekt
     * ein Name für die Formular Klasse angegeben wurde. Wenn nicht, dann wird der Name der Formular Klasse über 
     * Konventionen erstellt. 
     * Nach der Konvention befinden sich die Formular Klassen im gleichen Modul im Ordner "models/forms". Der
     * Name der Formular Klasse ist in der Einzahl. 
     * Ist der Name der Value Klasse beispielsweise Admin_Model_Value_Role, dann wäre der Name für die 
     * Formular Klasse ohne weitere Angaben Admin_Model_Form_Role
     * @return string
     */
    public function getFormClass()
    {
        if($this->_formClass != null)
        {
            return $this->_formClass;
        }
        
        $valueClass = get_class($this);
        $formClass = str_replace('Value', 'Form', $valueClass);
        
        $this->setFormClass($formClass);
        return $formClass;
    }
    
    /**
     * Setzt den Namen der Formularklasse
     * @param string $formClass
     * @return App_Model_Value_Abstract
     */
    public function setFormClass($formClass)
    {
        $this->_formClass = $formClass;
        return $this;
    }
    
    /**
    * Overload: diese Methode setzt automatisch die Werte im $this->_data array, wenn der angegebene Key
    * in $this->_allowed erlaubt wurde
    *
    * @param string $key
    * @param mixed $value
    * @return void
    * @throws App_Model_Exception
    */
    public function __set($key, $value)
    {
        if(!in_array($key, $this->_allowed))
        {
            throw new App_Model_Exception("Der Wert \"".$key."\" kann nicht gesetzt werden, da er f&uuml;r das Value Objekt nicht erlaubt ist!");
        }
        $this->_data[$key] = $value;
    }
    
    /**
     * Overload: Wert auslesen
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (!array_key_exists($key, $this->_data)) 
        {
            trigger_error("Der key <i>".$key."</i> existiert in ".get_class($this)." nicht!", E_USER_WARNING);
            return null;
        }
        return $this->_data[$key];
    }
    
    /**
     * Overload: Überprüfen, ob Wert gesetzt ist
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->_data[$key]);
    }
    
    /**
     * Overload: Wert löschen
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        if($this->__isset($key))
        {
            unset($this->_data[$key]);
        }
    }
    
    /**
     * Wird ausgeführt, wenn eine nicht vorhandene Methode ausgeführt werden soll
     * Die Methode simuliert getter und setter für die in $this->_data vorhandenen Daten
     * Es wird angenommen, dass getter Methoden als getProperty() und setter Methoden als setProperty()
     * aufgerufen werden (also jeweils get bzw set gefolgt vom property namen, wobei der erste Buchstabe
     * des Property Namens groß geschrieben wird)
     * 
     * @param $name Name der aufgerufenen Methode
     * @param array $arguments Argumente, mit denen die Methode aufgerufen wurde
     * @return void
     * @throws App_Model_Exception
     */
    public function __call($name, $arguments)
    {
        //abtrennen der ersten 3 buchstaben der aufgerufenen methode. dort darf nur get oder set stehen
        $type = substr($name, 0, 2);
        $key = strtolower(substr($name, 3));
        
        if($type == "get")
        {
            $this->__get($key);
        }
        elseif($type == "set")
        {
            $this->__set($key, $arguments[0]);
        }
        else
        {
            throw new App_Model_Exception("Die Methode ".$name." existiert in ".get_class($this)." nicht");
        }
    }
}