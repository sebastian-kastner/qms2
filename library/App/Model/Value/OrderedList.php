<?php
/**
 * Abstrakte Klasse, die von konkreten Value Klassen erweitert wird, falls diese Value Objekte in einer geordneten,
 * eindimensionalen Liste dargestellt werden
 *
 * @category   App
 * @package    App_Model
 * @subpackage Value
 * @author     kastners
 *
 */
abstract class App_Model_Value_OrderedList extends App_Model_Value_Abstract
{
    protected $_position = 'position';
    
    /**
     * Einfügen eines Datensatzes. Überschreibt die insert() Methode der abstrakten Value Klasse
     * @see Model/Value/App_Model_Value_Abstract#insert()
     */
    public function insert()
    {
        $this->getGateway()->checkAcl(App_Acl::ADD);
        $groupId = null;
        $group = $this->getGateway()->getGroupListBy();
        
        //keine position angegeben => ans ende der liste
        if(empty($this->_data[$this->_position]))
        {
            $where = ($group != null) ? $group. " = ".$this->$group : null;
            $num_rows = $this->getGateway()->getNumRows($where);
            $this->_data[$this->_position] = $num_rows + 1;
        }
        
        //position angegeben => wird an die position geschrieben, die restliche tabelle wird angepasst
        else
        {
            $db = $this->getTable()->getAdapter();
            $where = '';
            if($group != null)
            {
                $groupId = $this->$group;
                $where = $group." = ".$this->$group .  " AND ";
            }
            
            $newPos = $this->getGateway()->getNewPos($this->_data[$this->_position], $groupId);
            $where .= $this->_position .' >= '.$newPos;
            $expr = new Zend_Db_Expr('('.$db->quoteIdentifier($this->_position).' + 1)');
            $this->getTable()->update((array($this->getPositionCol() => $expr)), $where);
        }
        return parent::insert();
    }
    
    /**
     * Datensatz bearbeiten. Vor dem eigentlichen Schreiben des Datensatzes wird die Position der restlichen 
     * Datensätze angepasst und die neue Position gespeichert. Danach wird Datensatz mit allen anderen Feldern gespeichert.
     * @see Model/Value/App_Model_Value_Abstract#update()
     */
    public function update()
    {
        $this->getGateway()->checkAcl(App_Acl::EDIT);
        $pos = $this->getPositionCol();
        $newPos = $this->_data[$pos];
        
        $group = $this->getGateway()->getGroupListBy();
        $newGroup = null;
        if($group != null)
        {
            $newGroup = $this->$group;
        }
        $this->getGateway()->moveTo($this->getPrimaryValue(), $newPos, $newGroup);
        
        //positionswert aus dem wertearray entfernen, damit der über moveTo ermittelte wert nicht überschrieben wird
        //ist nötig um ungültige werte (negative werte, zu große werte) herauszufiltern
        unset($this->_data[$pos]); 
        $update = parent::update();
        $this->_data[$pos] = $newPos;
        return $update;
    }
        
    /**
     * Speichert den Datensatz und nur den Datensatz. Im Gegensatz zu der save() Methode der OrderedList Klasse werden
     * hier keine Berechnungen für die Position vorgenommen sondern einfach der die Werte im $_data Array in die Datenbank
     * geschrieben.
     * (Wird benötigt, um Rekursion bzw Endlosschleifen v.a. in der moveTo() zu vermeiden.)
     * @see Model/Value/App_Model_Value_Abstract#save()
     */
    public function saveOnly()
    {
        return parent::save();
    }
    
    /**
     * Löscht den Datensatz und passt die position nachfolgender Datensätze an
     * @see Model/Value/App_Model_Value_Abstract#delete($id)
     */
    public function delete()
    {
        $id = $this->getPrimaryValue();
        return $this->getGateway()->delete($id);
    }
    
    /**
     * Bewegt den Datensatz um eine Position nach oben und passt die Position des anderen betroffenen Datensatzes an
     * @return void
     */
    public function moveUp()
    {
        $this->getGateway()->moveUp($this);
        return;
    }
    
    /**
     * Bewegt den Datensatz um eine Position nach unten und passt die Position des anderen betroffenen Datensatzes an
     * @return void
     */
    public function moveDown()
    {
        $this->getGateway()->moveDown($this);
        return;
    }
    
    /**
     * Bewegt den Datensatz an die in $pos angegebene Stelle und passt die restlichen Positionen im Baum an.
     * @param $pos
     * @return void
     */
    public function moveTo($pos)
    {
        $this->getGateway()->moveTo($this, $pos);
        return;
    }
    
    /**
     * Gibt die Position eines Datensatzes zurück. 
     * @param int $id (opt)
     * @return int
     */
    protected function getPosition($id = null)
    {
        if($id == null)
        {
            if(is_numeric($this->_data[$this->_position]))
            {
                return $this->_data[$this->_position];
            }
            else
            {
                return $this->getPositionById($this->_data[$this->position]);
            }
        }
        elseif(is_numeric($id))
        {
            return $this->getPositionById($id);
        }
        elseif(!is_numeric($this->_data[$this->_position]) && $id == null)
        {
            $id = $this->_data[$this->getPrimary()];
            if(!is_numeric($id))
            {
                throw new App_Model_Exception('Konnte keine Position für den Datensatz ermitteln');
            }
            return $this->getPositionById($id);
        }
    }
    
    /**
     * Liest zu einem Datensatz, dessen ID gegeben ist die Position in der Liste aus und gibt die Position zurück
     * @param int $id
     * @return int
     */
    protected function getPositionById($id, $populate = true)
    {
        $value = $this->getGateway()->fetch($id)->toArray();
        if($populate)
        {
            $this->populate($value); 
        }
        return $value[$this->_position];
    }
    
    /**
     * Gibt den Namen der Spalte zurück, in der der Positionswert gespeichert wird
     * @return string
     */
    public function getPositionCol()
    {
        return $this->_position;
    }
}