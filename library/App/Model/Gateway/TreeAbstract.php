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
abstract class App_Model_Gateway_TreeAbstract extends App_Model_Gateway_Abstract
{
    /**
     * Liest den Baum aus
     * @param array $options zusätzliche Optionen für den Query (Optional)
     * @return array
     */
    public function getTree($options = null)
    {
        $this->checkAcl(App_Acl::VIEW);
        return $this->getTable()->getTree($options);
    }
    
    /**
     * Liest die Kinderelemente zu einer gegebenen ID aus
     * @param int $id
     * @return array
     * @throws App_Model_Exception
     */
    public function getChilds($id, $options = null)
    {
        $this->checkAcl(App_Acl::VIEW);
        $id = (int) $id;
        if(!is_int($id))
        {
            throw new App_Model_Exception("Es wurde eine ungültige ID übergeben! Die ID muss ein Zahlenwert sein!");
        }
        return $this->getTable()->getChilds($id, $options);
    }
    
    /**
     * Liest die Breadcrumbs (also den Pfad) für ein Value Objekt aus
     * @param int $id id des auszulesenden datensatzes
     * @param array $options (optional) zusätzliche optionen für den query
     * @return array
     * @throws App_Model_Exception
     */    
    public function getBreadcrumbs($id, $options = null)
    {
        $this->checkAcl(App_Acl::VIEW);
        $id = (int) $id;
        if(!is_int($id))
        {
            throw new App_Model_Exception("Es wurde eine ungültige ID übergeben! Die ID muss eine Zahl sein!");
        }
        return $this->getTable()->getBreadcrumbs($id, $options);
    }
    
    /**
     * Liest die direkten Nachfahren zu einer gegebenen ID aus
     * @param int $id
     * @return array
     * @throws App_Model_Exception
     */
    public function getDirectDescendants($id)
    {
        $this->checkAcl(App_Acl::VIEW);
        $id = (int) $id;
        if(!is_int($id))
        {
            throw new App_Model_Exception("Es wurde eine ungültige ID übergeben! Die ID muss eine Zahl sein!");
        }
        return $this->getTable()->getDirectDescendants($id);
    }
    
    /**
     * Liest den direkt übergeordneten Eintrag zu einer ID aus
     * @param int $id
     * @return array
     */
    public function getDirectParent($id)
    {
        $this->checkAcl(App_Acl::VIEW);
        $id = (int) $id;
        if(!is_int($id))
        {
            throw new App_Model_Exception("Es wurde eine ungültige ID übergeben! Die ID muss eine Zahl sein!");
        }
        return $this->getTable()->getDirectParent($id);
    }
    
    /**
     * Bewegt den Datensatz mit der übergebenen ID im Baum nach oben
     * @param int $id ID des zu verschiebenden Datensatzes
     * @return void
     */
    public function moveUp($id)
    {
        $this->checkAcl(App_Acl::EDIT);
        $this->getTable()->moveUp($id);
    }
    
    /**
     * Bewegt den Datensatz mit der übergebenen ID im Baum nach unten
     * @param int $id ID des zu verschiebenden Datensatzes
     * @return void
     */
    public function moveDown($id)
    {
        $this->checkAcl(App_Acl::EDIT);
        $this->getTable()->moveDown($id);
    }
}