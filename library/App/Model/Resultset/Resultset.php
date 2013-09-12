<?php
/**
 * Allgemeine Resultset Klasse, die die abstrakte Resultset Klasse erweitert. 
 * Die Klasse macht es möglich, dass Resultset Klassen, die den Namenskonventionen entsprechen und in denen 
 * keine weiteren Einstellungen nötig sind überflüssig werden und nicht mehr angelegt werden müssen. 
 * @author root
 * @category   App
 * @package    App_Model
 * @subpackage Resultset
 */
class App_Model_Resultset_Resultset extends App_Model_Resultset_Abstract
{
    /**
     * Konstruktor. Entsprecht dem Konstruktor der abstrakten Resultset Klasse, ist aber um den Parameter 
     * Value Class erweitert. Dieser wird im Objekt gespeichert.
     * @param $results
     * @param App_Model_Gateway_Abstract $gateway
     * @param string $valueclass Name der zu verwenden Value Klasse
     */
    public function __construct($results, App_Model_Gateway_Abstract $gateway = null, $valueclass = null)
    {
        if($valueclass == null)
        {
            throw new App_Model_Exception('Dem Resultset wurde keine Value Klasse übergeben!');
        }
        
        $this->_valueClass = $valueclass;
        
        //konstruktor der abstrakten klasse ausführen
        parent::__construct($results, $gateway);
    }
}