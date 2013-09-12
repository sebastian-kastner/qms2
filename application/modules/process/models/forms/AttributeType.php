<?php
/**
 * Formular für die Attributtypen
 * @see App_Form
 * @package Process
 * @subpackage Form
 * @author kastners
 *
 */
class Process_Model_Form_AttributeType extends App_Form
{
    public function init()
    {
        //breite des formulars festlegen
        $this->getDecorator('data')->setOption('style', 'width:70%');
        
        $name = new Zend_Form_Element_Text('name', array(
            'label' => 'Bezeichnung',
            'required' => true
        ));
        $this->addElement($name);
        
        $pos = new Zend_Form_Element_Text('position', array(
            'label' => 'Position',
            'size' => 3
        ));
        $this->addElement($pos);
        
        $submit = new Zend_Form_Element_Submit('submit', array(
            'Label' => 'Weiter',
            'ignore' => true
        ));
        $this->addElement($submit);
    
        $this->decorateElements();
        $this->createSubmitDecorators($submit);
        
        $id = new Zend_Form_Element_Hidden('process_attribute_type_id');
        $this->addElement($id);
    }
}