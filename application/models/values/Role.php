<?php
/**
 * Value Klasse für die Rollen
 * @uses App_Model_Value_Abstract
 * @author kastners
 * @package Admin
 * @subpackage Value
 *
 */
class Default_Model_Value_Role extends App_Model_Value_TreeAbstract
{   
    protected $_tableClass = 'Default_Model_DbTable_AclRoles';
    protected $_formClass = 'Admin_Model_Form_Role';
    protected $_entityName = 'Rolle';
}