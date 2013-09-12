<?php
/**
 * Abstrakte Klasse, die von konkreten Value Klassen erweitert wird, falls diese Value Objekte in einem Baum
 * dargestellt werden
 * 
 * @category   App
 * @package    App_Model
 * @subpackage Value
 * @author     kastners
 *
 */
abstract class App_Model_Value_TreeAbstract extends App_Model_Value_Abstract
{
    /**
     * Index, unter dem die ID des Elternelements für eventuelle insert und update operationen übergeben werden
     * (wird in $data gespeichert und muss im formular, aus dem die daten kommen, den gleichen
     *  namen haben)
     * @var string
     */
    protected $_parent_id = 'parent_id';
    
    /**
     * Index, unter dem die ursprüngliche ID des Elternelements gespeichert wird. Diese ID wird für
     * update Operationen benötigt, bei denen das Elternelement geändert wurde
     * @var string
     */
    protected $_old_parent_id = 'old_parent_id';
    
    /**
     * Liest die Breadcrumbs (also den Pfad) für ein Value Objekt aus
     * @return array
     * @throws App_Model_Exception
     */    
    public function getBreadcrumbs($options = null)
    {
        $this->getGateway()->checkAcl(App_Acl::VIEW);
        if(!$this->checkPrimary())
        {
            throw new App_Model_Exception("Es können keine Breadcrumbs erzeugt werden, da für das Value Objekt keine ID gespeichert ist!");
        }
        return $this->getTable()->getBreadcrumbs($this->_data[$this->getPrimary()], $options);
    }
    
    /**
     * Gibt die Children des Value Objektes zurück
     * @return array
     * @throws App_Model_Exception
     */
    public function getChilds($options = null)
    {
        $this->getGateway()->checkAcl(App_Acl::VIEW);
        if(!$this->checkPrimary())
        {
            throw new App_Model_Exception("Es können keine Childs erzeugt werden, da für das Value Objekt keine ID gespeichert ist!");
        }
        return $this->getTable()->getChilds($this->_data[$this->getPrimary()], $options);
    }
    
    /**
     * Gibt die Siblings des Value Objekts zurück
     * @return array
     */
    public function getSiblings()
    {
        $this->getGateway()->checkAcl(App_Acl::VIEW);
        if(!$this->checkPrimary())
        {
            throw new App_Model_Exception("Es können keine Siblings erzeugt werden, da für das Value Objekt keine ID gespeichert ist!");
        }
    }
    
    /**
     * Schiebt das Value Objekt in der Hierarchie um eine Position nach oben
     * @return App_Model_Value_TreeAbstract
     */
    public function moveUp()
    {
        $this->getGateway()->checkAcl(App_Acl::EDIT);
        if(!$this->checkPrimary())
        {
            throw new App_Model_Exception("Der Eintrag kann nicht verschoben werden, da für das Value Objekt keine ID gespeichert ist!");
        }
        return $this->getTable()->moveUp($this->getPrimaryValue());
    }
    
    /**
     * Schiebt das Value Objekt in der Hierarchie um eine Position nach unten
     * @return App_Model_Value_TreeAbstract
     */
    public function moveDown()
    {
        $this->getGateway()->checkAcl(App_Acl::EDIT);
        if(!$this->checkPrimary())
        {
            throw new App_Model_Exception("Der Eintrag kann nicht verschoben werden, da für das Value Objekt keine ID gespeichert ist!");
        }
        return $this->getTable()->moveDown($this->getPrimaryValue());
    }
    
    /**
     * Insert Methode überschreiben (es müssen neue lft und rgt Werte ermittelt werden)
     * @see Model/Value/App_Model_Value_Abstract#insert()
     */
    public function insert()
    {   
        $this->getGateway()->checkAcl(App_Acl::ADD);
        $parent = $this->_data[$this->_parent_id];
        $data = $this->stripUnallowedKeys($this->_data);
        $data = $this->clearProtectedProperties($data);
        $data = $this->addAdditionalInsertData($data);
        
        $insert = $this->getTable()->insert($data, $parent);
        //nach dem das objekt in die datenbank geschrieben wurde hat es in der datenbank eine id
        //diese id wird für eventuelle weitere operationen direkt im objekt gespeichert
        $this->_data[$this->getPrimary()] = $insert;
        $this->setLastAction('insert');
        return $insert;
    }
    
    /**
     * Update Funktion überschreiben, um lft und rgt Werte des Baums neu zu berechnen
     * @see Model/Value/App_Model_Value_Abstract#update()
     */
    public function update()
    {
        $this->getGateway()->checkAcl(App_Acl::EDIT);
       //tauschen der elternelemente
         $this->getTable()->changeParent($this->getPrimaryValue(), @$this->_data[$this->_parent_id]);
          return parent::update();
    }
}