<?php
/**
 * In einem Resultset werden ein oder mehrere Value Objekte in einem iterierbaren Objekt gespeichert
 * @category   App
 * @package    App_Model
 * @subpackage Resultset
 * @author     kastners
 * @uses       Iterator
 * @uses       Countable
 */
//TODO: handling von reinem verschachtelten array und einem array mit value objekten verbessern 
//(wann wird in value objekte konvertiert? wie wird dann in toArray() zurückkonvertiert? 

class App_Model_Resultset_Abstract implements Countable, Iterator
{    
    /**
     * Name der Value Klasse, die für die einzelnen Items verwendet wird
     * @var string
     */
    protected $_valueClass;
    
    /**
     * Gateway Objekt
     * @var App_Model_Gateway_Abstract
     */
    protected $_gateway;
    
    /**
     * Der Array mit den einzelnen items
     * @var array
     */
    protected $_resultSet = array();
    
    /**
     * Aktuelle Position im Array
     * @var int
     */
    protected $_position = 0;

    /**
     * Konstruktor
     * Erzeugt anhand der in $result übergebenen Daten einen Array mit den in Value Objekten
     * Die Value Objekte sind vom in $_valueClass festgelegten Typ. 
     *  
     * @param array|Traversable $results 
     * @param App_Model_Gateway_Abstract $gateway
     * @return void
     * @throws App_Model_Exception
     */
    public function __construct($results, App_Model_Gateway_Abstract $gateway = null)
    {
        if($gateway === null)
        {
            throw new App_Model_Exception("Kein Gateway Objekt angegeben!");
        }
        $this->setGateway($gateway);
        
        $valueClass = $this->getValueClass();
        
        if(is_object($results) && method_exists($results, 'toArray'))
        {
            $this->_resultSet = $results->toArray();
        }
        elseif(is_array($results))
        {
            $this->_resultSet = $results;
        }
        else
        {
            throw new App_Model_Exception("Die dem Resultset &uuml;bergebenen Daten m&uuml;ssen ein Array oder ein Objekt mit einer toArray() Methode sein!");
        }
    }
    
    /**
     * Setzt das Gateway Objekt
     * @param $gateway Gateway Objekt
     * @return App_Model_Resultset_Abstract
     */
    public function setGateway(App_Model_Gateway_Abstract $gateway)
    {
        $this->_gateway = $gateway;
    }
    
    /**
     * Gibt das Gateway zurück
     * @return App_Model_Gateway_Abstract
     */
    public function getGateway()
    {
        return $this->_gateway;
    }
    
    /**
     * Gibt den Namen der Value Klasse zurück. Wenn $_valueClass leer ist, wird der Name nach anhand des
     * Namens der Resultset Klasse erstellt.
     * @return string
     */
    public function getValueClass()
    {
        if($this->_valueClass != null)
        {
            return $this->_valueClass;
        }
        
        $resultClass = get_class($this);
        $valueClass = str_replace('Resultset', 'Value', $resultClass);
        $length = strlen($valueClass);
        
        if($valueClass{($length-1)} == 's')
        {
            $valueClass = substr($valueClass, 0, ($length-1));
        }
        return $valueClass;
    }
    
    /**
     * Liefert das Resultset als Array zurück
     * @return array
     */
    public function toArray()
    {
        return $this->_resultSet;
    }
    
    
    /**
     * Implementiert die count() Methode für das Countable Interface
     * Zählt die Items im Array _resultSet
     * @return int
     */
    public function count()
    {
        return count($this->_resultSet);
    }
    
    /**
     * Implementiert die current() Methode für das Iterator Interface
     * Gibt den aktuellen Datensatz zurück
     * @return bool|App_Model_Value_Abstract
     */
    public function current()
    {
        //$data = $this->_resultSet[$this->_position];
        $data = current($this->_resultSet);
        $valueClass = $this->getValueClass();
        $valueObject = new $valueClass($data, array('gateway' => $this->getGateway()));
        
        return $valueObject;
    }
    
    /**
     * Implementiert die next() Methode für das Iterator Interface
     * Gibt den nächstne Datensatz zurück (wird nach jedem Durchlauf der foreach Schleife aufgerufen)
     * @return void
     */
    public function next()
    {
        //$this->_position++;
        next($this->_resultSet);
    }
    
    /**
     * Implementiert die rewind() Methode für das Iterator Interface
     * Setzt den internen Zeiger des Arrays _resultSet zurück
     * @return void
     */
    public function rewind()
    {
        reset($this->_resultSet);
    }
    
    /**
     * Implementiert die key() Methode für das Iterator Interface
     * Gibt den key des aktuellen Datensatzes zurück
     * @return mixed
     */
    public function key()
    {
        return key($this->_resultSet());
        //return $this->_position;
    }
    
    /**
     * Implementiert die valid() Methode für das Iterator Interface
     * Überprüft nach dem aufruf von rewind() oder next(), ob das aktuelle element existiert 
     * @return boolean
     */
    public function valid()
    {
       if(current($this->_resultSet))
       {
           return true;
       }
       return false;
    }
    
    /**
     * Löscht eine Zeile aus dem Resultset
     * @param int|string $key index der zu löschenden zeile
     * @return void
     */
    public function unsetRow($key)
    {
        unset($this->_resultSet[$key]);
    }
}