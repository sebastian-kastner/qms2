<?php
/**
 * Formular für die Prozessattribute
 * @see App_Form
 * @package Process
 * @subpackage Form
 * @author kastners
 *
 */
class Process_Model_Form_ProcessAttribute extends App_Form
{    
    public function init()
    {        
        //breite des formulars festlegen
        $this->getDecorator('data')->setOption('style', 'width:70%');
        
        $model = new Process_Model_AttributeTypes();
        $types = new Zend_Form_Element_Select('process_attribute_type_id');
        $types->setLabel('Attribut Typ');
        $types->addMultiOptions($model->fetchAttributeNames());
        $this->addElement($types);
        
        $name = new Zend_Form_Element_Text('name', array(
            'label' => 'Bezeichnung',
            'required' => true
        ));
        $this->addElement($name);
        
        require(APPLICATION_PATH . "/configs/dynamicProcessMethods.php");
        $formtype = new Zend_Form_Element_Select('form_type');
        $formtype->setLabel('Attributwert');
        $type_options = array(
                      '' => '-- Bitte wählen --', 
                      'textarea' => 'Textfeld'
                    );
        $type_options = array_merge($type_options, $dynamicProcessMethods);
        $formtype->addMultiOptions($type_options);
        $formtype->setRequired(true);
        $this->addElement($formtype);
        
        $pos = new Zend_Form_Element_Text('position', array(
            'label' => 'Position',
            'size' => 3
        ));
        $this->addElement($pos);
        
        $is_active = new Zend_Form_Element_Radio('is_active');
        $is_active->setRequired(true)
                  ->setLabel('Anzeigen')
                  ->setMultiOptions(array(0 => 'Nein', 1 => 'Ja'));
        $this->addElement($is_active);
        
        
        
        $submit = new Zend_Form_Element_Submit('submit', array(
            'Label' => 'Weiter',
            'ignore' => true
        ));
        $this->addElement($submit);
    
        $this->decorateElements();
        $this->createSubmitDecorators($submit);
        
        $id = new Zend_Form_Element_Hidden('process_attribute_id');
        $this->addElement($id);
        
    }
}