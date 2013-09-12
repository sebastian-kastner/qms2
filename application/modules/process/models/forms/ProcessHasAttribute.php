<?php
/**
 * Formular/Validator für die process_has_attribute tabelle
 * @see Zend_Form
 * @package Process
 * @subpackage Form
 * @author kastners
 *
 */
class Process_Model_Form_ProcessHasAttribute extends Zend_Form
{
    public function init()
    {
        $value = new Zend_Form_Element_Text('attribute_value');
        $value->addValidator('NotEmpty');
        $this->addElement($value);
    }
}