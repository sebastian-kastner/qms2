<?php
/**
 * Value Klasse für die Verbindungstabelle zwischen Benutzern und Rollen
 * @uses App_Model_Value_Abstract
 * @author kastners
 * @package Admin
 * @subpackage Value
 *
 */
class Default_Model_Value_UserHasRole extends App_Model_Value_Abstract
{   
    protected $_tableClass = 'Default_Model_DbTable_AclUserHasRole';
}