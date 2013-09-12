<?php

/**
 * Gateway für die Prozesse
 * @author kastners
 * @see App_Model_Gateway_Abstract
 */
class Process_Model_ProcessHasAttribute extends App_Model_Gateway_Abstract
{
    protected $_valueClass = 'Process_Model_Value_ProcessHasAttribute';
    protected $_resultClass = 'Process_Model_Resultset_ProcessesHasAttribute';
    protected $_resource = App_Acl::PROCESS_ATTRIBUTES;
    
}