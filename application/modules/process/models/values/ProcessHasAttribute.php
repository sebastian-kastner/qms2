<?php
/**
 * Value Klasse für die Prozesse
 * @uses App_Model_Value_Abstract
 * @author kastners
 * @package Admin
 * @subpackage Value
 *
 */
class Process_Model_Value_ProcessHasAttribute extends App_Model_Value_Abstract
{
    protected $_allowed = array(
        'pha_id',
        'process_attribute_id',
        'process_id',
        'attribute_value',
        'created_by',
        'date_created',
        'created_by',
        'date_updated',
        'updated_by',
        'is_active'
    );
    protected $_tableClass = 'Process_Model_DbTable_ProcessHasAttribute';
    
    /**
     * Erweitert die Methode von App_Model_Value_Abstract zum Speichern eines Datensatzes
     * um einige für die Prozessattribute spezifischen Dinge 
     */
    public function save()
    {
        //falls kein gültiger primärschlüssel übergeben wurde wird dieser gelöscht
        if(!is_numeric($this->_data[$this->getPrimary()]))
        {
            unset($this->_data[$this->getPrimary()]);
        }
        if($this->_data['attribute_value'] == '')
        {
           if(!empty($this->_data[$this->getPrimary()]))
           {
               return parent::delete();
           }
           return;
        }
        
        return parent::save();
    }
}