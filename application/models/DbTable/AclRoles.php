<?php
class Default_Model_DbTable_AclRoles extends App_Db_Tree
{
    protected $_name = 'acl_roles';
    protected $_primary = 'role_id';
    //protected $_sequence = 'role_id_sequence';
    protected $_columns = array('role_name');
}