<?php
/**
 * Abstrakte Klasse, die von konkreten Gateway Klassen erweitert wird, falls das Gateway mit einer Tabelle
 * verknüpft ist, die einen Baum darstellt
 *
 * @category   App
 * @package    App_Model
 * @subpackage Gateway
 * @author     kastners
 *
 */

abstract class App_Model_Gateway_OrderedList extends App_Model_Gateway_Abstract
{
    /**
     * Bietet die Möglichkeit, in einer Tabelle mehrere geordnete, eindimensionale Listen zu speichern. Dafür
     * muss hier der Name der Spalte angegeben werden, anhand der die Liste gruppiert werden soll. 
     * @var string
     */
    protected $_groupListBy = null;
    
    /**
     * Liest alle Einträge aus und sortiert die Einträge nach dem positionsfeld
     * @see Model/Gateway/App_Model_Gateway_Abstract#fetchAll($where, $order, $perpage, $offset)
     */
    public function fetchAll($where = null, $order = null, $perpage = null, $offset = null)
    {
        $this->checkAcl(App_Acl::VIEW);
        $value = $this->getValue();
        $pos = $value->getPositionCol();
        if($order == null)
        {
            $order = $pos;
        }
        elseif(is_string($order))
        {
            $order = array($pos, $order);
        }
        elseif(is_array($order))
        {
            array_merge(array($pos), $order);
        }
        return parent::fetchAll($where, $order, $perpage, $offset);
    }
    
    /**
     * Löscht den Datensatz und passt die Positionen der restlichen Datensätze an
     * @param int $id ID des zu löschenden Datensatzes
     * @return int Anzahl der gelöschten Datensätze
     * @see Model/Gateway/App_Model_Gateway_Abstract#delete($id)
     */
    public function delete($id)
    {
        $this->checkAcl(App_Acl::DEL);
        $table = $this->getTable();
        $value = $this->fetch($id);
        $position = $value->getPositionCol();
        
        $expr = new Zend_Db_Expr('('.$table->getAdapter()->quoteIdentifier($position).' - 1)');
        $where = $position." > ".$value->$position;
        
        if($this->_groupListBy != null)
        {
            $group = $this->_groupListBy;
            $where .= " AND ".$group." = ".$value->$group;
        }
        
        $table->update(array($position => $expr), $where);
        return parent::delete($id);
    }
    
    /**
     * Bewegt den Datensatz mit der angegebenen ID, bzw das übergebene Value Objekt um eine Position nach oben
     * @param int|App_Model_Value_Abstract $id
     * @return App_Model_Gateway_OrderedList
     */
    public function moveUp($id)
    {
        $this->checkAcl(App_Acl::EDIT);
        //aktuelle position des datensatzes auslesen
        $value = $this->fetch($id);
        $position = $value->getPositionCol();
        
        $oldPos = $value->$position;
        $newPos = $oldPos - 1;
        
        //wenn die position gleich 1 ist kann der datensatz nicht weiter nach oben verschoben werden und es wird abgebrochen
        if($oldPos == 1)
        {
            return $this;
        }
        
        $data = array($position => (int)$oldPos);
        
        $where = $this->getTable()->getAdapter()->quoteInto($position. " = ?", $newPos, Zend_Db::INT_TYPE);
        
        if($this->_groupListBy != null)
        {
            $group = $this->_groupListBy;
            $where .= " AND ".$group." = ".$value->$group;
        }
        
        $this->getTable()->update($data, $where);
        $this->getTable()->update(array($position => $newPos), $value->getPrimary() . " = ".$value->getPrimaryValue());
        
        return $this;
    }
    
    /**
     * Bewegt den Datensatz mit der angegebenen ID, bzw das übergebene Value Objekt um eine Position nach unten
     * @param int $id
     * @return App_Model_Gateway_OrderedList
     */
    public function moveDown($id)
    {
        $this->checkAcl(App_Acl::EDIT);
        
        //aktuelle position des datensatzes auslesen
        $value = $this->getValueObject($id);
        $position = $value->getPositionCol();
        
        //anzahl der datensätze bestimmen um die größtmögliche position zu bestimmen
        $group = $this->_groupListBy;
        $where = null;
        if($this->_groupListBy != null)
        {
            $where = $group . " = " . $value->$group;
        }
        $num_rows = $this->getNumRows($where);
        $oldPos = $value->$position;
        $newPos = $oldPos +  1;
        
        //abbruch, wenn die position größer oder gleich der anzahl der datensätze ist (dann steht er eh am ende)
        if($oldPos >= $num_rows)
        {
            return $this;
        }
        
        $table = $this->getTable();
        $data = array($position => (int)$oldPos);
        $where = $table->getAdapter()->quoteInto($position. " = ?", $newPos, Zend_Db::INT_TYPE);
        
        if($this->_groupListBy != null)
        {
            $group = $this->_groupListBy;
            $where .= " AND ".$group." = ".$value->$group;
        }
        
        $table->update($data, $where);
        $this->getTable()->update(array($position => $newPos), $value->getPrimary() . " = ".$value->getPrimaryValue());
        
        return $this;
    }
    
    /**
     * Bewegt den Datensatz mit der angegebenen ID, bzw das übergebene Value Objekt an die angegebene Stelle. 
     * Wenn die Stelle zu klein (kleiner 1) oder zu groß (größer als die Anzahl der Datensätze) wird die neue Position 
     * auf 1 bzw die Anzahl der Datensätze korrigiert
     * Gibt false zurück, wenn sich die Position des Datensatzes nicht geändert hat, ansonsten wird das Ergebnis von 
     * App_Model_Value_Abstract::save() zurückgegeben
     * @param int $id ID des zu verschiebenden Datensatzes
     * @param int $newPos neue Position für den Datensatz
     * @return mixed|bool
     */
    public function moveTo($id, $newPos, $newGroup = null)
    {
        //TODO: wenn die gruppen verändert wird => aus alter gruppe "löschen" (baum neu berechnen)
        $this->checkAcl(App_Acl::EDIT);
        $value = $this->getValueObject($id);
        $position = $value->getPositionCol();
        
        if($this->_groupListBy != null)
        {
            $group = $this->_groupListBy;
            $groupId = $value->$group;
            if($newGroup != null && $newGroup != $groupId)
            {
                return $this->changeGroup($value, $newPos, $newGroup);
            }
        }
        
        $newPos = (int)$newPos;
        $oldPos = $value->$position;
        
        //abbruch, falls sich an der position nichts geändert hat
        if($newPos == $value->$position)
        {
            return false;
        }
        
        $groupId = null;
        
        $newPos = $this->getNewPos($newPos, $groupId);
        $value->$position = $newPos;

        $table = $this->getTable();
        $db = $table->getAdapter();
        
        //datensatz wird nach unten verschoben -> erstellung der WHERE Klausel und der expression für das UPDATE
        if($newPos > $oldPos)
        {   
            $expr = new Zend_Db_Expr('('.$db->quoteIdentifier($position).' - 1)');
            $where = $position ." > ".$oldPos." AND ".$position . " <= ".$newPos;
        }
        //datensatz wird nach oben verschoben -> WHERE und UPDATE erstellen
        else
        {
            $expr = new Zend_Db_Expr('('.$db->quoteIdentifier($position).' + 1)');
            $where = $position . " < ".$oldPos." AND ".$position . " >= ".$newPos;
        }
        
        if($this->_groupListBy != null)
        {
            $group = $this->_groupListBy;
            $where .=  " AND ".$this->_groupListBy." = ".$value->$group;
        } 
        
        $table->update(array($position => $expr), $where);
        return  $table->update(array($position => $newPos), $value->getPrimary() . " = ".$value->getPrimaryValue());
    }
    
    /**
     * Verschiebt das angegebene value Objekt an die angegebene Position in einer anderen Gruppe
     * @param App_Model_Value_Abstract $value
     * @param int $newPos
     * @param int $newGroup
     * @return bool|int False bei Fehler, im Erfolgsfall die Anzahl der geschriebenen Datensätze
     */
    protected function changeGroup($value, $newPos, $newGroup)
    {
        $this->checkAcl(App_Acl::EDIT);
        $newPos = $this->getnewPos($newPos, $newGroup);
        
        //alte position bestimmen
        $position = $value->getPositionCol();
        $oldPos = $value->$position;
        //alte gruppe bestimmen
        $group = $this->_groupListBy;
        $oldGroup = $value->$group;
        
        $table = $this->getTable();
        $db = $table->getAdapter();
        
        //positionen in der alten gruppe anpassen
        $expr = new Zend_Db_Expr('('.$db->quoteIdentifier($position).' - 1)');
        $where =  $db->quoteIdentifier($position) . " > " . $oldPos;
        $where .= " AND " . $db->quoteIdentifier($group) . " = ".$oldGroup;
        $res1 = $table->update(array($position => $expr), $where);
        
        //positionen in der neuen gruppe anpassen
        $expr = new Zend_Db_Expr("(".$db->quoteIdentifier($position)." + 1)");
        $where =  $db->quoteIdentifier($position)." >= ".$newPos;
        $where .= " AND ".$db->quoteIdentifier($group)." = ".$newGroup;
        $res2 = $table->update(array($position => $expr), $where);
        
        if($newPos != $oldPos)
        {
            $where = $db->quoteIdentifier($value->getPrimary()) . " = " . $value->getPrimaryValue();
            $table->update(array($position => $newPos), $where);
        }
        
        return $res1 + $res2;
    }
    
    /**
     * Korrigiert die neue Position, falls nötig. 
     * @param int $newPos
     * @return int
     */
    public function getNewPos($newPos, $groupId = null)
    {
        $where = null;
        if($groupId != null)
        {
            $where = $this->_groupListBy . " = " . $groupId;
        }
        
        $num_rows = $this->getNumRows($where); //anzahl der datensätze auslesen
        $num_rows = ($num_rows == 0) ? 1 : $num_rows;
        
        $newPos = (int)$newPos;
        
        //korrigieren der position, falls diese zu groß ist
        if($newPos > $num_rows)
        {
            return $num_rows;
        }
        //position korrigieren, falls diese zu klein ist
        if($newPos < 1)
        {
            return 1;
        }
        return $newPos;
    }
    
    /**
     * Unterscheidet, ob $value eine ID oder ein Value Objekt ist. Wenn $value eine ID ist wird der Datensatz ausgelesen
     * und das erstellte Value Objekt zurückgegeben. Ist $value ein gültiges App_Model_Value_Abstract Objekt, so wird 
     * dieses Objekt zurückgegeben. 
     * @param int|App_Model_Value_Abstract $value
     * @return App_Model_Value_Abstract
     * @throws App_Model_Exception
     */
    protected function getValueObject($value)
    {
        if(is_numeric($value))
        {
            return $this->fetch($value);
        }
        elseif ($value instanceof App_Model_Value_Abstract)
        {
            return $value;
        }
        else
        {
            throw new App_Model_Exception('Es konnte kein Value Objekt erzeugt werden, da weder eine gültige ID noch 
                            ein gültiges Value Objekt übergeben wurde!');
        }
    }
    
    /**
     * Gibt $_groupListBy zurück
     * @return string
     */
    public function getGroupListBy()
    {
        return $this->_groupListBy;
    }
}