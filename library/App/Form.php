<?php
/**
 * Erweitert Zend_Form
 * Initialisiert die nicht standardmäßigen Dekoratoren und einen Übersetzer für die Fehlermeldungen
 * 
 * @author kastners
 * @category App
 * @package App_Form
 * @subpackage Form
 * @see Zend_Form
 */
class App_Form extends Zend_Form
{
    protected $title;
    protected $_values = array();
    
    /**
     * Das zum Formular gehörige value Objekt (meist das Value Objekt, das das Formular erzeugt hat selbst)
     * @var App_Model_Value_Abstract
     */
    protected $_valueObject = null;

    /**
     * Das zum Formular gehörige Gateway Objekt (meist das Gateway des Value Objekts, das das Formular erzeugt hat)
     * @var App_Model_Gateway_Abstract
     */
    protected $_gatewayObject = null;
    
    /**
     * Überschreibt den Konstruktor von Zend_Form
     * Initialisiert den "Übersetzer" für die Fehlermeldungen, initialisiert die Decorators im App 
     * Verzeichnis und setzt die Decorators für das Formular (Tabellen)
     * @param array $values (optional) werte, die im formular direkt gesetzt werden sollen
     * @param array $options (optional) optionen für das formular
     * @param App_Model_Value_Abstract (optional) Value Objekt, das das Formular aufruft, bzw zu dem das Formular
     *                                  "gehört". Wird nur gesetzt, wenn das Formular von einem Value Objekt aus
     *                                  aufgerufen wurde
     */
    public function __construct($values = null, $options = null, $valueObject = null)
    {             
        require_once(APPLICATION_PATH . "/configs/translations/ValidateDE.php");
        if(!empty($validateTranslator))
        {
            $translator = new Zend_Translate('array', $validateTranslator);
            Zend_Validate_Abstract::setDefaultTranslator($translator);
        }
        $this->addPrefixPath('App_Form_Decorator', 'App/Form/Decorator/', 'decorator');

        $this->setDecorators(array(
            'FormElements',
            array(
                array('data' => 'HtmlTag'),
                array('tag' => 'table', 'class' => 'form_table')
             ),
             'Form',
             'Title'
        ));

        //werte setzen, wenn welche übergeben wurden
        if($values != null)
        {
            if(is_array($values))
            {
                $this->_values = $values;
            }
            elseif (is_object($values) && method_exists($values, 'toArray'))
            {
                $this->_values = $values->toArray();
            }
        }
        
        //optionen setzen, wenn welche übergeben wurden
        if($options != null)
        {
            if (is_array($options)) {
                $this->setOptions($options);
            } elseif ($options instanceof Zend_Config) {
                $this->setConfig($options);
            }
        }
        
        //value Objekt und gateway Objekt setzen, falls ein value Objekt übergeben wurde
        if($valueObject != null && $valueObject instanceof App_Model_Value_Abstract)
        {
            $this->setValueObject($valueObject);
            $this->setGatewayObject($valueObject->getGateway());
        }
        
        //init methode aufrufen (wird von unterklassen erweitert)
        $this->init();
        
        if($values != null)
        {
            $this->populate($this->_values);
        }
    }
    
    /**
     * Dekoriert alle Elemente einheitlich
     * @return void
     */
    public function decorateElements()
    {
        $this->setElementDecorators(
            array(
                'ViewHelper',
                'Description',
                'Errors',
                array(
                    array('data' => 'HtmlTag'),
                    array('tag' => 'td')
                ),
                array(
                    'Label',
                    array('tag' => 'th')
                ),
                array(
                    array('row' => 'HtmlTag'),
                    array('tag' => 'tr')
                )
            ));
    }
    
    /**
     * Setzt die Dekoratoren für einen Submit Button
     * @param Zend_Form_Element_Submit $submit
     * @return void
     */
    public function createSubmitDecorators(Zend_Form_Element_Submit $submit)
    {
        $submit->setDecorators(array(
            'ViewHelper',
            array(
                array('data' => 'HtmlTag'),
                array('tag' => 'td', 'colspan' => '2', 'class' => 'tfoot')  
            ),
            array(
                array('row' => 'HtmlTag'),
                array('tag' => 'tr')
            )
        ));
    }
    
    /**
     * Gibt den Wert zurück, der beim 
     * @param string|int $val
     * @return mixed
     */
    public function getVal($val)
    {
        if(array_key_exists($val, $this->_values))
        {
            return $this->_values[$val];
        }
        return null;
    }
    
    /**
     * Setzt den Titel für ein Formular
     * @param string $value
     * @return void
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }
    
    /**
     * Liest den Titel des Formulars aus
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
      
    /**
     * Formular mit Inhalt füllen
     * Überschreibt die populate() Methode von Zend_Form. Wenn ein Objekt übergeben wird, dann wird versucht,
     * ob dieses eine toArray() Methode hat. Wenn ja, dann wird das Objekt mit dieser Methode zu einem Array
     * gemacht und anschließend wird die populate() Methode von Zend_Form aufgerufen 
     *
     *
     * @param  array|stdObject $values
     * @return Zend_Form
     */
    public function populate($values)
    {
        if(!is_array($values) && method_exists($values, 'toArray'))
        {
            $values = $values->toArray();
        }
        elseif (!is_array($values))
        {
            throw new Zend_Form_Exception("An populate() muss ein Array oder ein Objekt mit einer toArray Methode übergeben werden!");
        }
        return parent::populate($values);
    }
    
    /**
     * Setzt das zum Formular gehörige Value Objekt
     * @param App_Model_Value_Abstract $valueObject
     * @return App_Form
     */
    public function setValueObject(App_Model_Value_Abstract $valueObject)
    {
        $this->_valueObject = $valueObject;
        return $this;
    }
    
    /**
     * Gibt das Value Objekt zurück, von dem aus das Formular erzeugt wurde. 
     * ACHTUNG! Diese Methode kann nur verwendet werden, wenn das Formular auch von einem Value Objekt erzeugt 
     * oder wenn beim Erstellen des Formulars ein Value Objekt übergeben wurde!
     * @return App_Model_Value_Abstract
     */
    public function getValueObject()
    {
        if($this->_valueObject == null)
        {
            throw new App_Model_Exception('Es kann kein Value Objekt zurückgegeben werden, da keines gesetzt wurde! 
                                           Wenn das Formular nicht von einem Value Objekt erzeugt wurde muss ein
                                           dazugehöriges Value Objekt beim Erstellen des Formulars übergeben werden!');
        }
        return $this->_valueObject;
    }
    
    /**
     * Setzt das zum Formular gehörige Gateway Objekt
     * @param App_Model_Gateway_Abstract $valueObject
     * @return App_Form
     */
    public function setGatewayObject(App_Model_Gateway_Abstract $gatewayObject)
    {
        $this->_gatewayObject = $gatewayObject;
    }
    
    /**
     * Gibt das Gateway Objekt von dem Value Objekt zurück, welches das Formular erzeugt hat. 
     * ACHTUNG! Diese Methode kann nur verwendet werden, wenn das Formular auch von einem Value Objekt erzeugt 
     * oder wenn beim Erstellen des Formulars ein Value Objekt übergeben wurde!
     * @return App_Model_Gateway_Abstract
     */
    public function getGatewayObject()
    {
        if($this->_gatewayObject == null)
        {
            throw new App_Model_Exception('Es kann kein Gateway Objekt zurückgegeben werden, da keines gesetzt wurde! 
                                           Wenn das Formular nicht von einem Value Objekt erzeugt wurde muss ein
                                           dazugehöriges Value Objekt beim Erstellen des Formulars übergeben werden!');
        }
        return $this->_gatewayObject;
    }
    
}