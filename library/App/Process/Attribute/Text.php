<?php
/**
 * Attribute, deren Inhalt aus reinem Text bestehen
 * 
 * @category   App
 * @package    App_Process
 * @subpackage Attribute
 * @author     kastners
 */
class App_Process_Attribute_Text extends App_Process_Attribute_Abstract
{   
    /**
     * Name des Partials, der für reine Textattribute verwendet wird
     * @var string
     */ 
    protected $_partial = 'text.phtml';
    
    /**
     * Beim Erzeugen des Objekts wird der Content gesetzt
     * @param array $attribute
     * @return void
     */
    public function __construct($attribute)
    {
        //diese zeile ist nur nötig, falls der übergebene wert noch irgendwie geparst oder verändert werden soll
        //$this->setContent($this->getContent());
        $this->setData($attribute);
        $this->setPartial($this->_partial);
    }
}