<?php
/**
 * Formular für die Rollen
 * @see App_Form
 * @package Admin
 * @subpackage Form
 * @author kastners
 *
 */
class Admin_Model_Form_Role extends App_Form
{    
    public function init()
    {
        //breite des formulars festlegen
        $this->getDecorator('data')->setOption('style', 'width:70%');
        
        $name = new Zend_Form_Element_Text('role_name', array(
            'label' => 'Rollenbezeichnung',
            'required' => true
        ));
        $this->addElement($name);
        
        $model = new Default_Model_Roles();
        $role_id = $this->getValue('role_id');
        $is_root = false;
        if($role_id != null)
        {
            $parent = $model->getDirectParent($role_id);
            if(!$parent)
            {
                $is_root = true;
            }
        }
        else
        {
            $parent = null;
        }
        //wenn parent vorhanden => anzeige der elternelemente (ansonsten ist es das root element,
        //dieses kann eh nicht verschoben werden)
        if(!$is_root)
        {
            $parents = new Zend_Form_Element_Select('parent_id');
            $parents->setLabel('Unterrolle von');
            if($parent != null)
            {
               $parents->setValue($parent['role_id']);
            }
            $where = App_Db_Tree::NODE_ALIAS.'.active = 1';
            $roles = $model->getTree(
                               //array(App_Db_Tree::WHERE => $where)
                             );
             $spacer = array('', '--', '----', '------', '--------', '----------');
             $parents->addMultiOption(
                      '',
                      '-- Bitte wählen --'
                  );
             foreach($roles AS $role)
            {
               $parents->addMultiOption(
                            $role['role_id'],
                            $spacer[$role['level']].$role['role_name']
                       );
            }
            $this->addElement($parents);
        }
        
        
        $description = new Zend_Form_Element_Textarea('role_description', array(
            'cols' => 40, 
            'rows' => 5,
            'ErrorMessages' => array('Sie müssen eine Beschreibung angeben!'),
            'Label' => 'Beschreibung'
        ));
        $this->addElement($description);
        
        $submit = new Zend_Form_Element_Submit('submit', array(
            'Label' => 'Weiter',
            'ignore' => true
        ));
        $this->addElement($submit);
    
        $this->decorateElements();
        $this->createSubmitDecorators($submit);
        
        $id = new Zend_Form_Element_Hidden('role_id');
        $this->addElement($id);
        
        $parent_id = new Zend_Form_Element_Hidden('old_parent_id');
        $parent_id->setValue($parent['role_id']);
        $this->addElement($parent_id);
    }
}