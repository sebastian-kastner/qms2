<?php
class Admin_Model_Form_Right extends App_Form
{
    public function init()
    {
        //breite des formulars festlegen
        $this->getDecorator('data')->setOption('style', 'width:60%');
        $this->getDecorator('data')->setOption('id', 'rightsEdit');
        
        //acl objekt aus der registry holen um die resourcen namen bestimmen zu können        
        $resource_model = new Default_Model_Resources();
        $resourceList = $resource_model->fetchAll()->toArray();
        $acl = Zend_Registry::get('acl');
        $resources = $acl->getResources();
        
        //request vom front controller holen und role_id auslesen
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $model = $this->getGatewayObject();
        
        $role_id = $request->getParam($model->getRightTypeCol());
        $rights = $model->getFullRights($role_id);
        
        $privileges = $acl->getPrivileges();
        foreach($resourceList AS $key => $resource)
        {
            $level = $resource['level']-1;
            $element = new Zend_Form_Element_MultiCheckbox($resource['resource_id'], array(
                'multiOptions' => $privileges,
                'label' => $resource['resource_name'],
                'separator' => '</td><td>'
            ));
            $element->setDecorators(array(
                'ViewHelper',
                'Description',
                'Errors',
                array(
                    array('data' => 'HtmlTag'),
                    array('tag' => 'td')
                ),
                array(
                    'Label',
                    array('tag' => 'th', 'style' => 'margin-left:'.($level*20).'px;')
                ),
                array(
                    array('row' => 'HtmlTag'),
                    array('tag' => 'tr')
                )
            ));
            
            //wenn ein element des untersten levels erstellt wurde wird eine zusätzliche kopfzeile eingefügt
            if($level == 0)
            {
                $element->addDecorator('ExtraTableRow', array('cols' => $privileges));
            }
            
            if(array_key_exists($resource['resource_id'], $rights))
            {
                $element->setValue($rights[$resource['resource_id']]);
            }
            $this->addElement($element);
        }
        
        $submit = new Zend_Form_Element_Submit('submit', array(
            'Label' => 'Weiter',
            'ignore' => true
        ));
        $this->addElement($submit);
        
        $this->createSubmitDecorators($submit);        
        $submit->getDecorator('data')->setOption('colspan', '5');
    }
}