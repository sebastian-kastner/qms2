<?php
/**
 * Formular für die Rollen
 * @see App_Form
 * @package Admin
 * @subpackage Form
 * @author kastners
 *
 */
class Admin_Model_Form_User extends App_Form
{    
    public function init()
    {
    	//breite des formulars festlegen
        $this->getDecorator('data')->setOption('style', 'width:60%');
        //id setzen
        $this->getDecorator('data')->setOption('id', 'user-form');
        
        $username = new Zend_Form_Element_Text('username', array(
            'label' => 'Benutzername',
            'required' => true
        ));        
        $validator = new Zend_Validate_Db_NoRecordExists(array(
            'table' => $this->getValueObject()->getTableName(),
            'field' => 'username'
        ));
        $validator->setMessage("Dieser Benutzername existiert bereits!");
        $username->addValidator($validator);
                
        $this->addElement($username);
        
        $email = new Zend_Form_Element_Text('email', array(
            'label' => 'E-Mail Adresse:',
            'required' => true
        ));
        $email->addValidator('EmailAddress');
        
        $emailValidator = new Zend_Validate_Db_NoRecordExists(array(
            'table' => $this->getValueObject()->getTableName(),
            'field' => 'email'
        ));
        $emailValidator->setMessage("Diese E-Mail Adresse wird bereits verwendet!");
        $email->addValidator($emailValidator);
        
        $this->addElement($email);
        
        $pswd = new Zend_Form_Element_Password('pswd', array(
            'label' => 'Passwort',
            'required' => true
        ));

        $pswd->addValidator('StringLength', false, array(4,15));
        $pswd->addErrorMessage('Wählen Sie ein Passwort mit einer Länge von 4 bis 15 Zeichen');
        $this->addElement($pswd);
        
        $confirmPswd = new Zend_Form_Element_Password('confirm_pswd', array(
            'label' => "Passwort wiederholen",
            'required' => true
        ));
        
        $confirmPswd->addValidator('Identical', false, array('token' => 'pswd'));
        $confirmPswd->addErrorMessage('Die Passwörter stimmen nicht überein!');
        $this->addElement($confirmPswd);
        
        $this->addElement(new Zend_Form_Element_Text('firstname', array('label' => 'Vorname')));
        $this->addElement(new Zend_Form_Element_Text('lastname', array('label' => 'Nachname')));
                
        $submit = new Zend_Form_Element_Submit('submit', array(
            'label' => 'Weiter',
            'ignore' => true
        ));
        
        $this->addElement($submit);
        $this->decorateElements();
        
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
        $roles = new Zend_Form_Element_Hidden("roles", array('value' => $value));
        $this->addElement($roles);
        
        $this->createSubmitDecorators($submit);
    }
}