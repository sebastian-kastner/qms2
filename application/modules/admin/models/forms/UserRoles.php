<?php
/**
 * Formular für die Rollen der Benutzer
 * @see App_Form
 * @package Admin
 * @subpackage Form
 * @author kastners
 *
 */
class Admin_Model_Form_UserRoles extends App_Form
{    
    public function init()
    {
        $user = $this->getValueObject();        
        $user_roles = $user->getRoles(false);
        if(count($user_roles) > 0)
        {
            $value = " " . implode(" ", $user_roles) . " ";
        }
        else
        {
            $value = " ";
        }
        
        //breite des formulars festlegen
        $this->getDecorator('data')->setOption('style', 'width:60%');
        //id setzen
        $this->getDecorator('data')->setOption('id', 'user-form');

        $roles = new Zend_Form_Element_Hidden("roles", array('value' => $value, 'label' => "Rollen für \"".$user->getValue("username")."\""));
        $this->addElement($roles);
        
        $submit = new Zend_Form_Element_Submit('submit', array(
            'label' => 'Weiter',
            'ignore' => true
        ));
        $this->addElement($submit);
        $this->decorateElements();
        
        $this->createSubmitDecorators($submit);
    }
}