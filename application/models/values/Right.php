<?php
/**
 * Value Klasse für die Rechte
 * @uses App_Model_Value_Abstract
 * @author kastners
 * @package Admin
 * @subpackage Value
 *
 */
class Default_Model_Value_Right extends App_Model_Value_Abstract
{   
    protected $_tableClass = 'Default_Model_DbTable_AclRights';
    protected $_formClass = 'Admin_Model_Form_Right';
    protected $_entityName = 'Right';
}