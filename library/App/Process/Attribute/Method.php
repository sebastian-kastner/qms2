<?php
/**
 * Attributklasse für Attribute, die ihren Attributwert aus einem dynmischen Methodenaufruf beziehen
 * 
 * @category   App
 * @package    App_Process
 * @subpackage Attribute
 * @author     kastners
 */
class App_Process_Attribute_Method extends App_Process_Attribute_Abstract
{
    /**
     * Liste der Methoden, die für einen dynamischen Methodenaufruf erlaubt sind
     * (wird in application/configs/dynamicProcessMethods.php definiert)
     * @var array
     */
    protected $_allowedMethods;
    
    /**
     * 
     * @param array|string $attribute
     * @param App_Model_Value_Abstract $value
     * @return void
     */
    public function __construct($attribute, App_Model_Value_Abstract $value)
    {
        if(is_string($attribute))
        {
            $type = $attribute;
            $this->setType($type);
        }
        elseif(is_array($attribute))
        {
            $type = $attribute[$this->getTypeCol()];
            $this->setData($attribute);
        }
        else
        {
            throw new App_Process_Exception('Attribut konnte nicht erzeugt werden, da kein gültiger Attributtyp übergeben wurde');
        }
        
        //Liste mit den erlaubten Methode einbinden
        if(!isset($dynamicProcessMethods))
        {
            require(APPLICATION_PATH . "/configs/dynamicProcessMethods.php");
        }
        $this->_allowedMethods = $dynamicProcessMethods;
        
        //if(in_array($type, $this->_allowedMethods))
        if(key_exists($type, $this->_allowedMethods))
        {
            $res = $value->$type();
            if(is_object($res) && method_exists($res, 'toArray'))
            {
                $res = $res->toArray();
                
            }
            $this->setContent($res);
        }
        else
        {
            throw new App_Process_Exception('Die Methode "'.$type.'" konnte nicht aufgerufen werden, da ihr Aufruf nicht erlaubt ist');
        }
        
        $this->setPartial();
    }
    
    /**
     * Erzeugt aus dem Typ den Namen des dazugehörigen Partials
     * @param string $type
     * @return string
     */
    public function getPartialName($type = null)
    {
        if($type == null)
        {
            $type = $this->getType();
        }
        
        if(substr($type, 0, 3) == 'get')
        {
            $type = substr($type, 3);
        }
        
        $inflector = new Zend_Filter_Inflector(':string');
        $inflector->setRules(array(':string'  => array('Word_CamelCaseToDash', 'StringToLower')));
        $partial = $inflector->filter(array('string' => $type));
        
        return $partial. ".phtml";
    }
    
    /**
     * Setzt den Partial
     * @see Process/Attribute/App_Process_Attribute_Abstract#setPartial($partial)
     */
    public function setPartial($partial = null)
    {
        $partial = ($partial == null) ? $this->getPartialName() : $partial;
        parent::setPartial($partial);
    }
}