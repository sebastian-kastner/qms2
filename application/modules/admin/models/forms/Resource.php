<?php
/**
 * Formular für die Rollen
 * @see App_Form
 * @package Admin
 * @subpackage Form
 * @author kastners
 *
 */
class Admin_Model_Form_Resource extends App_Form
{    
    public function init()
    {        
        //breite des formulars festlegen
        $this->getDecorator('data')->setOption('style', 'width:70%');
        
        $name = new Zend_Form_Element_Text('resource_name', array(
            'label' => 'Resourcenbezeichnung',
            'required' => true,
            'description' => 'Bezeichnung, die im Administrationsbereich für die Resource angegeben wird'
        ));
        $this->addElement($name);
        
        $description = new Zend_Form_Element_Textarea('resource_description', array(
            'cols' => 40, 
            'rows' => 5,
            'Label' => 'Beschreibung',
            'Description' => 'Optionale Beschreibung'
        ));
        $this->addElement($description);
        
        $submit = new Zend_Form_Element_Submit('submit', array(
            'Label' => 'Weiter',
            'ignore' => true
        ));
        $this->addElement($submit);
        
        $this->decorateElements();
        $this->createSubmitDecorators($submit);
        
        $id = new Zend_Form_Element_Hidden('resource_id');
        $this->addElement($id);
    }
}