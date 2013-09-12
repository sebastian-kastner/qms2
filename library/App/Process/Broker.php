<?php
/**
 * Dient zum Zusammenstellen von Prozessinformationen (vor allem Prozessattribute) zu einem 
 * einzigen Prozess. 
 * Die Attribute können von verschiedenen Typen sein. Die Attribute können entweder reinen Text enthalten 
 * oder eine Referenz auf eine Methode, deren Rückgabewert dann als Attributwert gespeichert wird
 *
 * @category   App
 * @package    App_Process
 * @author     kastners
 *
 */
class App_Process_Broker
{    
    
    /**
     * Zum Prozess gehörige Attribute
     * @var array
     */
    protected $_attributes = array();   
    
    /**
     * Fügt dem Prozess ein neues Attribut hinzu. 
     * @param string|App_Process_Attribute_Abstract $type der Typname oder alternativ ein Attribut Objekt
     * @param string $text Der Textwert des Attributs. Nur bei Attributen vom Texttyp nötig. (opt)
     * @return void
     */
    public function addAttribute(App_Process_Attribute_Abstract $attribute)
    {
        //wenn ein objekt vom typ App_Process_Attribute_Abstract übergeben wird wird dieses direkt gespeichert
        if($attribute instanceof App_Process_Attribute_Abstract)
        {
            $this->_attributes[] = $attribute;
            return;
        }
    }
    
    /**
     * Gibt den Prozess mit allen Attributen als Array zurück
     * @return array
     */
    public function toArray()
    {
        $attributes = array();
        foreach($this->_attributes AS $att)
        {
            $attributes[] = $att->toArray();
        }
        
        return $attributes;
    }    
}