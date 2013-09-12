<?php
/**
 * Gateway für die process_types
 * @author kastners
 * @see App_Model_Gateway_Abstract
 */
class Process_Model_ProcessTypes extends App_Model_Gateway_OrderedList
{
    protected $_resource = App_Acl::PROCESS_PROCESSTYPES;
}