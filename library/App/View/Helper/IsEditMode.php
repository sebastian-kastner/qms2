<?php
/**
 * Zum bestimmen, ob man sich im Bearbeitungsmodus befindet.
 * @category   App
 * @package    App_View
 * @subpackage Helper
 * @see Zend_View_Helper_Abstract 
 */

require_once 'Zend/View/Helper/Abstract.php';

class App_View_Helper_IsEditMode extends Zend_View_Helper_Abstract
{
    /**
     * Bearbeitungsmodus
     * @var bool
     */
    protected $_editMode;
    
    //TODO: ACL integrieren!
    /**
     * Konstruktor. Ermittelt den Bearbeitungsmodus und speichert diesen ab.
     */
    public function __construct()
    {
       $this->_editMode = App_EditMode::isEditMode();
    }
    
    /**
     * Gibt den Bearbeitungsmodus zurück
     * @return bool
     */
    public function IsEditMode()
    {
        return $this->_editMode;
    }
}