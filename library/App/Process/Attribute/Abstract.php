<?php
/**
 * Abstrakte Klasse von der die verschiedenen Attributtypen (Text, Methode) abgeleitet werden
 *
 * @category   App
 * @package    App_Process
 * @subpackage Attribute
 * @author     kastners
 *
 */

class App_Process_Attribute_Abstract
{    
    /**
     * Der Name, unter dem Texttypen gespeichert werden
     * @var string
     */
    const TEXTTYPE = 'textarea';
    
    /**
     * Spalte bzw Index, in dem der Typ gespeichert (und übergeben) wird
     * @var string
     */
    protected $_typeCol = 'form_type';
    
    /**
     * Spalte bzw Index, in dem der Textinhalt für das Attribut gespeichert (und übergeben) wird (nur bei Text typ)
     * @var string
     */
    protected $_contentCol = 'attribute_value';
    
    /**
     * Array mit den Daten des Attributs (Name, Attribut ID, Content, etc)
     * @var string|array
     */
    protected $_data;
    
    /**
     * Setzt den Partial
     * @param string $partial
     * @return void
     */
    public function setPartial($partial)
    {
        $this->_data['partial'] = $partial;
    }
    
    /**
     * Gibt den Partial zurück
     * @return string
     */
    public function getPartial()
    {
        return $this->_data['partial'];
    }
    
    /**
     * Gibt den Attributtyp zurück
     * @return string
     */
    public function getType()
    {
        return $this->_data[$this->getTypeCol()];
    }
    
    /**
     * Setzt den Typ
     * @param string $type
     * @return void
     */
    public function setType($type)
    {
        $this->_data[$this->getTypeCol()] = $type;
    }
    
    /**
     * Gibt den Namen der Spalte zurück, in der der Typ gespeichert wird
     * @return string
     */
    public function getTypeCol()
    {
        return $this->_typeCol;
    }
    
    /**
     * Speichert die übergebenen Daten als Attributeigenschaften ab
     * @param $data
     * @return void
     */
    public function setData(array $data)
    {
        $this->_data = $data;
    }
    
    /**
     * Setzt den Content. Erlaubt sind Strings und Arrays
     * @param string|array $content
     * @return void
     */
    public function setContent($content)
    {
        if(is_string($content) OR is_array($content))
        {
            $this->_data[$this->getContentCol()] = $content;
        }
    }
    
    /**
     * Gibt den Content zurück. 
     * @return string|array 
     */
    public function getContent()
    {
        return $this->_data[$this->getContentCol()];
    }
    
    /**
     * Gibt den Namen der Spalte zurück, in der der Content gespeichert wird
     * @return void
     */
    public function getContentCol()
    {
        return $this->_contentCol;
    }
    
    /**
     * Setzt den Namen der Spalte, in der der Content gespeichert wird
     * @param string $contentCol
     * @return void
     */
    public function setContentCol($contentCol)
    {
        if(is_string($contentCol))
        {
            $this->_contentCol = $contentCol;
        }
        else
        {
            throw new App_Process_Exception('Der Name der Spalte für den Content konnte nicht geändert werden!
                                             Es wurde ein ungültiger Wert übergeben!');
        }
    }
    
    /**
     * Gibt das Attribut als Array zurück
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }
}