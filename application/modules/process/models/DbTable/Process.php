<?php
class Process_Model_DbTable_Process extends App_Db_Tree
{
    protected $_name = 'process';
    protected $_primary = 'process_id';
    
    protected $_columns = array('name', 'notation');
}