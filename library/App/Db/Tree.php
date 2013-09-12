<?php
/**
 * Klasse zum Erstellen und Verwalten von Datenbanktabellen, die ein Nested Set abbilden.
 * Für die Datenbankabfragen werden mit Zend_Db_Select select Objekte erzeugt. Die Klasse bietet jeweils
 * Methoden, die die Operationen direkt ausführen und Methoden, die nur ein Select Objekt zurückgeben. Ein
 * zurückgegebenes Select Objekt kann dann einfach erweitert werden.
 * @author kastners
 * @package App
 * @subpackage Db
 *
 */
abstract class App_Db_Tree extends Zend_Db_Table_Abstract
{
    const WHERE = 'where';
    const HAVING = 'having';
    const ORDER = 'order';
    const LIMIT = 'limit';
    const OFFSET = 'offset';
    const RIGHT = 'right';
    const UP = 'up';
    const DOWN = 'down';
    
    /**
     * Alias, der in den Queries für die parent Tabelle verwendet wird
     * @var string
     */
    const PARENT_ALIAS = 'parent';
    
    /**
     * Alias, der in den Queries für die node Tabelle verwendet wird
     * @var string
     */
    const NODE_ALIAS = 'node';
    
    /**
     * Alias, der in den Queries für die root Tabelle verwendet wird
     * @var string
     */
    const ROOT_ALIAS = 'root';
    
    /**
     * Name der Spalte, in der der "left" Wert für das Nested Set gespeichert wird
     * @var string
     */
    protected $_lft = 'lft';
    
    /**
     * Name der Spalte, in der der "right" Wert für das Nested Set gespeichert wird
     * @var string
     */
    protected $_rgt = 'rgt';
    
    /**
     * Name der Spalte, in der die ID des Elternelements gespeichert wird
     * @var string 
     */
    protected $_parentCol = 'parent';
    
    /**
     * Name der Spalte, in der der temporäre Wert "updated" gespeichert wird (wird für das 
     * verschieben von Knoten in andere Elternelemente benötigt)
     * @var unknown_type
     */
    protected $_upd = 'updated';
    
    /**
     * Index, unter dem die ID des Parents gespeichert wird, wenn ein Datensatz eingetragen oder bearbeitet wird
     * (entspricht idR dem Namen des input Feldes, welches vor dem eintragen/bearbeiten abgeschickt wird)
     * @var string
     */
    protected $_parent = 'parent';
    
    /**
     * Array mit Spalten, die standardmäßig aus der Tabelle gelesen werden sollen
     * @var array
     */
    protected $_columns = null;
    
    /**
     * Speichert den Namen des Primärschlüssels. Heißt treePrimary, da primary schon von Zend_Db_Table_Abstract
     * belegt ist
     * @var string
     */
    protected $_treePrimary = null;
    
    /**
     * Setzt die Optionen und ruft danach den Konstruktor der Oberklasse (Zend_Db_Table_Abstract) auf
     * @param array $options
     * @return void
     * @see Zend_Db_Table_Abstract::__construct()
     * @throws App_Db_Tree_Exception
     */
    public function __construct($options = null)
    {
        parent::__construct();
        $this->setOptions($options);
        
        if($this->getLeft() == null)
        {
            throw new App_Db_Tree_Exception('Die Spalte mit dem LEFT Wert wurde nicht angegebenen!');
        }
        if($this->getRight() == null)
        {
            throw new App_Db_Tree_Exception('Die Spalte mit dem RIGHT Wert wurde nicht angegebenen!');
        }
    }
    
    /**
     * Speichert die Daten in $data als Unterpunkt von $parent (wird als letztes Unterelement angelegt)
     * @param array $data einzufügende Daten
     * @param int $parent ID des Elternelements 
     * @see Db/Table/Zend_Db_Table_Abstract#insert($data)
     */
    public function insert(array $data, $parent)
    {
        if(!is_numeric($parent))
        {
            throw new App_Db_Tree_Exception('Es wurde kein gültiges Elternelement angegeben');
        }
        $parent = $this->find($parent)->toArray();
        
        if(count($parent) != 1)
        {
            throw new App_Db_Tree_Exception('Das zugehörige Elternelement konnte nicht ermittelt werden!');
        }
        $parent = $parent[0];
        
        $db = $this->getAdapter();
        $lft = $this->getLeft();
        $lft_quoted = $db->quoteIdentifier($lft);
        $rgt = $this->getRight();
        $rgt_quoted = $db->quoteIdentifier($rgt);
        
        try 
        {
            $db->beginTransaction();        
            //lft werte anpassen
            $update = array($lft => new Zend_Db_Expr('('.$lft_quoted . ' + 2)'));
            $where = $lft_quoted." > ".$parent[$rgt];
            $this->update($update, $where);
            
            //rgt werte anpassen
            $update = array($rgt => new Zend_Db_Expr('('.$rgt_quoted. ' + 2)'));
            $where = $rgt_quoted." >= ".$parent[$rgt];
            $this->update($update, $where);
            
            $data[$lft] = $parent[$rgt];
            $data[$rgt] = $parent[$rgt] + 1;
            $data[$this->_parentCol] = $parent[$this->getPrimary()];
    
            $insert = parent::insert($data);
            
            $this->getAdapter()->commit();
        }
        catch (Exception $e)
        {
            $db->rollBack();
            throw $e;
        }
        return $insert;
        
    }
    
    /**
     * Liest den Baum aus. Standardmäßig werden nur die in $_columns spezifizierten Felder, lft, rgt
     * die Anzahl der Kinder und der Level ausgelesen. Sortiert wird nach lft
     * @param array $options
     * @return array der Baum in einem eindimensionalen Array
     * @see getTreeSelect()
     */
    public function getTree(array $options = null)
    {
        $select = $this->getTreeSelect($options);
        $result = $select->query();
        return $result->fetchAll();
    }
    
    /**
     * Liest einen einzelnen Datensatz anhand seiner ID aus. Im Gegensatz zu Zend_Db_Table_Abstract::find()
     * ermittelt getNode() noch den Level und die Anzahl der Kinder vom Datensatz
     * @param int $id id des auszulesenden nodes
     * @param array $options (optional) weitere optionen für den query
     * @return array
     */
    public function getNode($id, $options = null)
    {
        $id = $this->getAdapter()->quote($id, Zend_Db::INT_TYPE);
        $opt = array(
                 self::WHERE => self::NODE_ALIAS.".".$this->getPrimary() . " = " .$id,
                 self::LIMIT => 1
               );
        $select = $this->getTreeSelect($opt);
        if(is_array($options))
        {
            $this->extendSelect($select, $options);
        }
        $res = $select->query()->fetchAll();
        return $res[0];
    }
    
    //TODO: ORDER teil verbessern. Bis jetzt wird ein zusätzliches order statement nur angehängt!
    /**
     * Erzeugt ein Zend_Db_Select Objekt, mit dem der Baum ausgelesen werden kann. Standardmäßig werden 
     * vom Baum die Felder lft, rgt, primärschlüssel, Anzahl der Kinder und der Level ausgegeben. 
     * In $options können weitere optionen für den select übergeben werden. 
     * Mögliche Optionen:
     *      columns => zusätzliche Spalten, die ausgelesen werden sollen (nur Array erlaubt!)
     *      where => zusätzliche WHERE Klausel. Wird mit AND an die bereits vorhandene angehängt (nur Strings erlaubt!)
     *      having => eine HAVING Klausel wird erstellt. Nur Strings erlaubt! 
     *      order => standardmäßig wird nur nach dem lft Feld (aufsteigend) sortiert. hier kann ein zweiter
     *               parameter für den ORDER Teil angehängt werden
     * @param $options
     * @return Zend_Db_Select
     */
    public function getTreeSelect(array $options = null)
    {
        $select = $this->getAdapter()->select();
        
        //$select->setIntegrityCheck(false);
        
        $rgt = $this->getRight();
        $lft = $this->getLeft();
        $table = $this->_name;
        
        //bestimmen der spalten, die auf jeden fall ausgelesen werden
        $columns = array(
                    'num_childs' => new Zend_Db_Expr("(".self::NODE_ALIAS.".".$rgt." - ".self::NODE_ALIAS.".".$lft." - 1)/2"),
                    'level' => '(COUNT(*) - 1)',
                    $lft,
                    $rgt,
                    $this->getPrimary()  
                  );
        
        //select zusammenstellen
        $select->from(array(self::NODE_ALIAS => $table), $columns)
               ->joinCross(array(self::PARENT_ALIAS => $table), array())
               ->where(self::NODE_ALIAS.".".$lft." BETWEEN ".self::PARENT_ALIAS.".".$lft." AND ".self::PARENT_ALIAS.".".$rgt)
               ->group(self::NODE_ALIAS.".".$lft);
        
        //Select Objekt um die in Options angegebenen Parameter erweitern
        $select = $this->extendSelect($select, $options);
        
        return $select;
    }
    
    /**
     * Gibt einen Array mit dem Pfad zum Eintrag mit der übergebenen ID zurück
     * @param int $id
     * @param array $options
     * @return array
     * @throws App_Db_Tree_Exception
     * @see getBreadcrumbsSelect()
     */
    public function getBreadcrumbs($id, $options = null)
    {
        if(!is_numeric($id))
        {
            throw new App_Db_Tree_Exception('Es wurde keine gültige ID übergeben!');
        }
        $select = $this->getBreadcrumbsSelect($id, $options);
        
        $result = $select->query();
        $breadcrumbs = $result->fetchAll();
        //root node entfernen
        //unset($breadcrumbs[0]);
        return $breadcrumbs;
    }
    
    /**
     * Erzeugt den query um den Pfad zum Eintrag mit der übergebenen ID auszulesen
     * @param int $id
     * @param array $options
     * @return Zend_Db_Select
     * @throws App_Db_Tree_Exception
     */
    public function getBreadcrumbsSelect($id, $options = null)
    {
        if(!is_numeric($id))
        {
            throw new App_Db_Tree_Exception('Es wurde keine gültige ID übergeben!');
        }
        
        $rgt = $this->getRight();
        $lft = $this->getLeft();
        $table = $this->_name;
        
        $select = $this->getAdapter()->select();
        
        //spalten für den query erstellen
        $columns = array($this->getPrimary(), $lft, $rgt, $this->_parentCol);

        if($options != null && key_exists(self::COLUMNS, $options) && is_array($options[self::COLUMNS]))
        {
            $columns = array_merge($columns, $options[self::COLUMNS]);
        }
        
        $select->from(array(self::PARENT_ALIAS => $table), array('parent_id' => $this->getPrimary()))
               ->joinCross(array(self::NODE_ALIAS => $table), array())
               ->columns($columns, self::NODE_ALIAS)
               ->where(self::PARENT_ALIAS.".".$lft." BETWEEN ".self::NODE_ALIAS.".".$lft." AND ".self::NODE_ALIAS.".".$rgt)
               ->where($this->getAdapter()->quoteInto(self::PARENT_ALIAS.".".$this->getPrimary()." = ?", $id, Zend_Db::INT_TYPE))
               ->order(self::NODE_ALIAS.".lft");
               
        $select = $this->extendSelect($select, $options);
        return $select;
    }
    
    /**
     * Liest die Unterpunkte zu einer ID aus
     * @param int $id
     * @param array $options
     * @return array
     */
    public function getChilds($id, $options = null)
    {
        if(!is_numeric($id))
        {
            throw new App_Db_Tree_Exception('Es wurde keine gültige ID übergeben!');
        }

        $select = $this->getChildsSelect($id, $options);
        
        $result = $select->query();
        return $result->fetchAll();
    }
    
    /**
     * Erstellt den Select, um die Unterpunkte zu einer ID auszulesen
     * @param int $id
     * @param $options
     * @return Zend_Db_Select
     */
    public function getChildsSelect($id, $options = null)
    {
        $select = $this->getAdapter()->select();
        $table = $this->_name;
        $lft = $this->getLeft();
        $rgt = $this->getRight();
        
        $columns = array($this->getPrimary(), 
                         $lft, 
                         $rgt, 
                         'num_childs' => 'ROUND(' . new Zend_Db_Expr("(".self::NODE_ALIAS.".".$rgt." - ".self::NODE_ALIAS.".".$lft." - 1)/2") . ')',
                         );
                         
        $select->from(array(self::PARENT_ALIAS => $table), array())
               ->joinCross(array(self::ROOT_ALIAS => $table), 
                           array('level' => 'COUNT('.self::ROOT_ALIAS.'.process_id)'))
               ->joinCross(array(self::NODE_ALIAS => $table), $columns)
               ->where(self::NODE_ALIAS.".".$lft." > ".self::ROOT_ALIAS.".".$lft." 
                       AND 
                       ".self::NODE_ALIAS.".".$lft." < ".self::ROOT_ALIAS.".".$rgt)
               ->where(self::NODE_ALIAS.".".$lft." > ".self::PARENT_ALIAS.".".$lft." 
                       AND 
                       ".self::NODE_ALIAS.".".$lft." < ".self::PARENT_ALIAS.".".$rgt)
               ->where($this->getAdapter()->quoteInto(self::PARENT_ALIAS.".".$this->getPrimary()." = ?", $id, Zend_Db::INT_TYPE))
               ->group(self::NODE_ALIAS.".lft")
               ->order(self::NODE_ALIAS.".lft")
               //->having('level = 2')
               ;
        
        $select = $this->extendSelect($select, $options);
        return $select;
    }
    
    /**
     * Liest nur die direkten Nachkommen zu einer ID aus
     * @param int $id
     * @return array
     */
    public function getDirectDescendants($id)
    {
        $select = $this->getTreeSelect();
        $select->where(self::NODE_ALIAS . ".".$this->getPrimary()." = ".$id);
        $select->limit(1);
        $result = $select->query();
        $parent = $result->fetch();

        $select = $this->getChildsSelect($id);
        $select->having('level = '. ($parent['level'] + 1));
        $result = $select->query();
        return $result->fetchAll();
    }
    
    /**
     * Liest nur den direkt übergeordneten Eintrag zu einer ID aus
     * @param int $id
     * @return array
     */
    public function getDirectParent($id)
    {
        $node = $this->find($id)->toArray();
        $node = $node[0];
        $lft = $node[$this->getLeft()];
        $rgt = $node[$this->getRight()];

        $select = $this->select();
        $select->where(
               $this->getLeft()." < ".$lft."
               AND 
               ".$this->getRight()." > ".$rgt)
               ->order($this->getLeft() . " DESC")
               ->limit(1);
        $result = $select->query();
        if($result->rowCount() > 0)
        {
            $row =  $result->fetchAll();
            return $row[0];
        }
        else
        {
            return null;
        }
    }
    
    /**
     * Gibt nur eine Ebene des Baums zurück.
     * @param $level der Level, der zurückgegeben werden soll
     * @return array 
     * @see getByLevelSelect()
     */
    public function getByLevel($level, $options = null)
    {
        $select = $this->getByLevelSelect($level, $options);
        $result = $select->query();
        return $result->fetchAll();
    }
    
    /**
     * Gibt ein select Objekt zurück, das nur eine Ebene des Baums ausliest
     * @param int $level auszulesende Ebene
     * @param options weitere Optionen (optional)
     * @return Zend_Db_Select
     * @see getTreeSelect()
     * @throws App_Db_Tree_Exception
     */
    public function getByLevelSelect($level, $options = null)
    {
        if(!is_numeric($level))
        {
            throw new App_Db_Tree_Exception("Kein Level zum Auslesen angegeben!");
        }
        $options = $this->addOption(self::HAVING, "level = ".$level, $options);
        return $this->getTreeSelect($options);
    }
    
    /**
     * Bewegt den Datensatz mit der übergebenen ID um eine Position nach oben
     * Alle Unterpunkte des Datensatzes werden mit dem Datensatz nach oben verschoben
     * @param int $id ID des zu verschiebenden Datensatzes
     * @return void
     */
    public function moveUp($id)
    {
        $this->moveNode($id, self::UP);
    }
    
    /**
     * Bewegt den Datensatz mit der übergebenen ID um eine Position nach unten
     * Alle Unterpunkte des Datensatzes werden mit dem Datensatz nach unte verschoben
     * @param int $id ID des zu verschiebenden Datensatzes
     * @return void
     */
    public function moveDown($id)
    {
        $this->moveNode($id, self::DOWN);
    }
    
    /**
     * Bewegt den Datensatz mit der übergebenen ID um eine Position nach oben oder nach unten
     * Die Richtung wird in $dir übergeben
     * @param int $id ID des zu verschiebenden Datensatzes
     * @param string $dir Richtung, in die verschoben werden soll (nach oben oder nach unten)
     * @return void
     */
    protected function moveNode($id, $dir)
    {
        //bestimmen, ob die lft und rgt werte von sibling bzw node erhöht oder verringert werden müssen
        if($dir == self::UP)
        {
            //node nach oben => lft und rgt von node verringern, lft und rgt von sibling erhöhen
            $node_op = ' - ';
            $sibling_op = ' + ';
        }
        elseif($dir == self::DOWN)
        {
            //node nach unten => lft und rgt von node erhöhen, lft und rgt von sibling verringern
            $node_op = ' + ';
            $sibling_op = ' - ';
        }
        else
        {
            throw new App_Db_Tree_Exception('Ungültige Richtung zum Verschieben angegeben!');
        }
        
        //zu verschiebenden eintrag auslesen
        $node = $this->getNode($id);
        if(count($node) != 1)
        {
            return;
        }
        $node = $node[0];
        //sibling auslesen, der vom verschieben direkt betroffen ist 
        if($dir == self::UP)
        {
            //zweite betroffene datensatz hat: rgt = node[lft] - 1
            $sibling_where = self::NODE_ALIAS.".".$this->getRight()." = ".($node[$this->getLeft()]-1);
        }
        else
        {
            //zweite betroffene datensatz hat: lft = node[rgt] + 1
            $sibling_where = self::NODE_ALIAS.".".$this->getLeft()." = ".($node[$this->getRight()]+1); 
        }
        
        $affected_sibling = $this->getTree(array(self::WHERE => $sibling_where));
        if(count($affected_sibling) != 1)
        {
            return;
        }
        $affected_sibling = $affected_sibling[0];
        
        //wert, um den der lft und der rgt wert vom node verändert werden müssen (entspricht childs vom sibling mal 2)
        $node_diff = ($affected_sibling['num_childs'] + 1) * 2;
        //wert, um den der lft und der rgt wert vom sibling verändert werden müssen
        $sibling_diff = ($node['num_childs'] + 1) * 2;
        $sibling_ids = $this->getIds($affected_sibling[$this->getLeft()], $affected_sibling[$this->getRight()]);
        
        $db = $this->getAdapter();
        $lft_quoted = $db->quoteIdentifier(($this->getLeft()));
        $rgt_quoted = $db->quoteIdentifier(($this->getRight()));
        
        try 
        {
            $db->beginTransaction();
            //node updaten
            $data = array(
                      $this->getLeft() => new Zend_Db_Expr($lft_quoted . $node_op .$node_diff),
                      $this->getRight() => new Zend_Db_Expr($rgt_quoted . $node_op . $node_diff)
                      );
            $where = $this->getBetweenWhereStr($node[$this->getLeft()], $node[$this->getRight()]);
            $this->update($data, $where);
            
            //sibling updaten
            $data = array(
                      $this->getLeft() => new Zend_Db_Expr($lft_quoted . $sibling_op .$sibling_diff),
                      $this->getRight() => new Zend_Db_Expr($rgt_quoted . $sibling_op . $sibling_diff)
                      );
            $where = new Zend_Db_Expr($this->getPrimary(). " IN (".implode(', ', $sibling_ids).")");
            $this->update($data, $where);
            
            $this->getAdapter()->commit();
        }
        catch (Exception $e)
        {
            $db->rollBack();
            throw $e;
        }
        
    }
    
    /**
     * Ändert das Elternelement eines Eintrags
     * Alle Unterelemente des zu verschiebenden Eitnrags werden mit verschoben
     * @param int $node_id ID des zu verschiebenden Eintrags
     * @param int $parent_id ID des neuen Elternelement
     * @param int $old_parent_id ID des alten Elternelements
     * @return bool
     */
    public function changeParent($node_id, $parent_id)
    {
        
        //überprüfung, ob parent_id und node_id unterschiedlich sind
        if($parent_id == $node_id)
        {
            //true als rückgabewert, ohne dass irgendwelche änderungen am baum
            return true;
        }
        
        //überprüfung, ob es sich bei dem zu verschiebenenden element um das root element handelt
        $old_parents = $this->getDirectParent($node_id);
        //wenn kein parent gefunden wurde => root => abbruch!
        if(!$node_id)
        {
            throw new App_Db_Tree_Exception("Das Root Element kann nicht verschoben werden!");
        }
        
        //überprüfung, ob neuer und alter parent unterschiedlich sind
        if($parent_id == $old_parents[$this->getPrimary()])
        {
            //wenn alter gleich neuem parent => true als rückgabe ohne irgendwelche änderungen am baum
            return true;
        }
        
        $lft = $this->getLeft(); //bezeichnung der "lft" spalte
        $lft_quoted = $this->getAdapter()->quoteIdentifier($lft); //lft spalte mit quotes für queries
        $rgt = $this->getRight(); //bezeichung der "rgt" spalte
        $rgt_quoted = $this->getAdapter()->quoteIdentifier($rgt); //rgt spalte mit quotes für queries
        $options = array(self::COLUMNS => array($lft, $rgt));
        $node = $this->getNode($node_id);  //daten zum zu verschiebenden node auslesen
        $parent = $this->getNode($parent_id);  //daten zum neuen elternelement auslesen
        $diff = ($node['num_childs'] + 1) * 2; //differenz, um die sich die lft und rgt werte ändern
        $primary = $this->getPrimary();  //bezeichung der spalte mit dem primärschlüssel
        $primary_quoted = $this->getAdapter()->quoteIdentifier($primary); //primary mit quotes für queries
        $upd = $this->getUpdateCol(); //bezeichnung der spalte mit dem update status
        $upd_where = $this->getAdapter()->quoteIdentifier($upd). " != 1";
        
        
        //transaction starten
        try 
        {
            $this->getAdapter()->beginTransaction();    
            //unterscheidung, welche art von verschiebung vorliegt
            
            //verschieben in ein übergeordnetes elternelement
            if($node[$lft] > $parent[$lft] && $node[$rgt] < $parent[$rgt])
            {
                //werte für node selbst anpassen
                $data = array(
                          $lft => ($parent[$rgt] - $diff),
                          $rgt => ($parent[$rgt] - 1),
                          $this->_parentCol => $parent_id,
                          $upd => 1
                        );
                $where = $primary_quoted. " = ".$node[$primary];
                $this->update($data, $where);
                
                //werte für die unterpunkte des nodes anpassen
                $sub_diff = $parent[$rgt] - $node[$rgt] - 1; //lft/rgt differenz bei unterpunkten des nodes
                $data = array(
                          $lft => new Zend_Db_Expr($lft_quoted . " + " . $sub_diff),
                          $rgt => new Zend_Db_Expr($rgt_quoted . " + " . $sub_diff),
                          $upd => 1
                        );
                $where = $upd_where. " AND ". $lft_quoted ." > ".$node[$lft]." 
                         AND ".$rgt_quoted ." < ".$node[$rgt];
                $this->update($data, $where);
                
                //werte der alten parents (exklusive root und direktem parent) anpassen
                $data = array(
                          $rgt => new Zend_Db_Expr($rgt_quoted . " - " . $diff),
                          $upd => 1
                        );
                $where = $upd_where." AND ".$lft_quoted." < ".$node[$lft]."
                         AND ".$lft_quoted . " > ".$parent[$lft]." AND ".$rgt_quoted . " > ".$node[$lft];
                $this->update($data, $where);
                
                //zur neuen position rechts liegende siblings anpassen
                $data = array(
                          $lft => new Zend_Db_Expr($lft_quoted. " - ".$diff),
                          $rgt => new Zend_Db_Expr($rgt_quoted. " - ".$diff),
                        );
                $where = $upd_where." AND ".$lft_quoted." > ".$node[$rgt]."
                         AND ".$rgt_quoted." < ".$parent[$rgt]; 
                $this->update($data, $where);
            }
            
            //verschieben "nach oben" (in einen links liegenden container)
            elseif($node[$lft] > $parent[$lft] && $node[$rgt] > $parent[$rgt])
            {
                //neues elternelement anpassen
                $data = array(
                          $rgt => new Zend_Db_Expr($rgt_quoted . " + ".$diff),
                          $upd => 1
                        );
                $where = $primary_quoted. " = ".$parent[$primary];
                $this->update($data, $where);
                
                //node selbst anpassen
                $data = array(
                          $lft => $parent[$rgt],
                          $rgt => ($parent[$rgt] - 1 + $diff),
                          $this->_parentCol => $parent_id,
                          $upd => 1
                        );
                $where = $primary_quoted. " = ".$node[$primary];
                $this->update($data, $where);
                
                //unterpunkte des nodes anpassen
                $subdiff = $node[$lft] - $node[$rgt];
                $data = array(
                          $lft => new Zend_Db_Expr($lft_quoted."-(".($node[$lft] - $parent[$rgt]).")"),
                          $rgt => new Zend_Db_Expr($rgt_quoted."-(".($node[$lft] - $parent[$rgt]).")"),
                          $upd => 1
                        );
                $where = $upd_where." AND ".$lft_quoted." > ".$node[$lft]." AND ".$rgt_quoted." < ".$node[$rgt];
                $this->update($data, $where);
    
                //nodes zwischen alter und neuer position anpassen
                $data = array(
                          $lft => new Zend_Db_Expr($lft_quoted . " + ".$diff),
                          $rgt => new Zend_Db_Expr($rgt_quoted . " + ".$diff),
                          $upd => 1
                        );
                $where = $upd_where . " AND ".$lft_quoted . " > " . $parent[$rgt] . " 
                         AND " . $rgt_quoted . " < ".$node[$lft];
                $this->update($data, $where);
                
    
                //neue parents (ohne direkten parent) "erweitern" (ergo: rgt vergrößern,
                //damit die neuen unterpunkte "reinpassen")
                $data = array(
                          $rgt => new Zend_Db_Expr($rgt_quoted . " + ".$diff),
                          $upd => 1
                        );
                $where = $upd_where . " AND ".$lft_quoted."<".$parent[$lft]." 
                         AND ".$rgt_quoted . ">".$parent[$rgt]." AND ".$rgt_quoted."<".$node[$lft];
                $this->update($data, $where);
                
                //alte parents analog zum letzten schritt "verkleinern"
                $data = array(
                          $lft => new Zend_Db_Expr($lft_quoted . " + ".$diff),
                          $upd => 1
                        );
                $where = $upd_where." AND ".$lft_quoted.">".$parent[$rgt]." 
                         AND ".$lft_quoted."<".$node[$lft]." AND ".$rgt_quoted." > ".$node[$rgt];
                $this->update($data, $where);
            }
            
            //verschieben "nach unten" (in einen rechts liegenden container)
            elseif($node[$lft] < $parent[$lft] && $node[$rgt] < $parent[$rgt])
            {
                //neues elternelement "erweitern" (bei gleichbleibendem rgt das lft verkleinern)
                $data = array(
                          $lft => new Zend_Db_Expr($lft_quoted . " - ".$diff),
                          $upd => 1
                        );
                $where = $primary_quoted. " = ".$parent[$primary];
                $this->update($data, $where);
                
                //node selbst anpassen
                $data = array(
                          $lft => ($parent[$rgt] - $diff),
                          $rgt => ($parent[$rgt] - 1),
                          $this->_parentCol => $parent_id,
                          $upd => 1
                        );
                $where = $primary_quoted. " = ".$node[$primary]." AND ".$upd_where;
                $this->update($data, $where);
                
                //unterpunkte des nodes verschieben
                $sub_diff = ($parent[$rgt] - 1) - $node[$rgt];
                $data = array(
                          $lft => new Zend_Db_Expr($lft_quoted." + ".$sub_diff),
                          $rgt => new Zend_Db_Expr($rgt_quoted." + ".$sub_diff),
                          $upd => 1
                        );
                $where = $lft_quoted." > ".$node[$lft]." AND ".$rgt_quoted." < ".$node[$rgt]. " 
                         AND ".$upd_where;
                $this->update($data, $where);
                
                //alte parents (ohne root etc) "verkleinern"
                $data = array(
                          $rgt => new Zend_Db_Expr($rgt_quoted." - ".$diff),
                          $upd => 1
                        );
                $where = $lft_quoted." < ".$node[$lft]." AND ".$rgt_quoted." > ".$node[$rgt]. " 
                         AND ".$rgt_quoted. " < ".$parent[$lft]." AND ".$upd_where;
                $this->update($data, $where);
                
                //punkte zwischen neuer und alter position anpassen
                $data = array(
                          $lft => new Zend_Db_Expr($lft_quoted. " - ".$diff),
                          $rgt => new Zend_Db_Expr($rgt_quoted. " - ".$diff),
                          $upd => 1
                );
                $where = $lft_quoted. " > ".$node[$rgt]." AND ".$rgt_quoted." < ".$parent[$rgt]." 
                         AND ".$upd_where;
                $this->update($data, $where);
                
                //neue parents (außer direktem elternelement) erweitern
                $data = array(
                          $lft => new Zend_Db_Expr($lft_quoted. " - ".$diff),
                          $upd => 1
                        );
                $where = $lft_quoted. " > ".$node[$rgt]." AND ".$lft_quoted. " < ".$parent[$rgt]. " 
                         AND ".$rgt_quoted." > ".$parent[$rgt]. " AND ".$upd_where;
                $this->update($data, $where);
            }
            
            //update werte wieder auf null zurücksetzen um neue verschiebungen zu ermöglichen
            $data = array($upd => 0);
            $where = $upd . " = 1";
            $this->update($data, $where);
            
            $this->getAdapter()->commit();
        }
        catch (Exception $e)
        {
            $this->getAdapter()->rollBack();
            throw $e;
        }    
        return true;
    }
    
    /**
     * Setzt die Optionen
     * @param array $options
     * @return App_Db_Tree
     */
    public function setOptions(array $options = null)
    {
        if($options == null)
        {
            return; 
        }
        
        foreach ($options as $key => $value) 
        {
            switch ($key) 
            {
                case self::COLUMNS:
                    $this->setColumns($value);
                    break;
                case self::LEFT:
                    $this->setLeft($value);
                    break;
                case self::RIGHT:
                    $this->setRight($value);
                    break;
            }
        }
    }
    
    /**
     * Liest die IDs zu Nodes aus, die zwischen einem bestimmten lft und rgt Wert liegen.
     * Mit den IDs können bei Änderungen an der Baumstruktur Überschneidungen bei den lft und rgt Werten verhindert werden
     * @param int $lft LEFT Wert
     * @param int $rgt RIGHT Wert
     * @return array array mit den IDs der Datensätze zwischen dem lft und rgt Wert
     */
    protected function getIds($lft, $rgt)
    {
        $where = $this->getBetweenWhereStr($lft, $rgt);
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from($this->_name, array($this->getPrimary() => $this->getPrimary()))
               ->where($where)
               ;
        $res = $select->query();
        $ids = array();
        while($row = $res->fetch())
        {
            $ids[] = $row[$this->getPrimary()];
        }
        return $ids;
    }
    
    /**
     * Liefert einen String mit der WHERE Bedingung für ein BETWEEN zwischen einem LFT und einem RGT Wert
     * @param int $lft LEFT Wert
     * @param int $rgt RIGHT Wert
     * @return string
     */
    protected function getBetweenWhereStr($lft, $rgt)
    {
        $db = $this->getAdapter();
        $where = $db->quoteIdentifier($this->getLeft()). " >= ".$lft." AND ".
                 $db->quoteIdentifier($this->getRight()). " <= ".$rgt;
        return $where;
    }
    
    /**
     * Fügt dem übergebenen $options array den $optionValue hinzu und gibt den neuen $options array zurück
     * Wenn für $optionType bereits ein Wert gespeichert ist werden der alte und der neue Wert zu einem 
     * Array zusammengefügt. Ist bereits ein Array gespeichert wird der neue Wert in den Array geschrieben.
     * Ist für den $optionType noch kein Wert gespeichert wird ein String an die Stelle geschrieben
     * @param string $optionType
     * @param array $options
     * @return array
     * @throws App_Db_Tree_Exception
     */
    protected function addOption($optionType, $optionValue, array $options = null)
    {
        if($options == null)
        {
            $options = array();
            $options[$optionType] = $optionValue;
        }
        else
        {
            if(empty($ptions[$optionType]))
            {
                $options[$optionType] = $optionValue;
            }
            elseif(is_array($options[$optionType]))
            {
                $options[$optionType][] = $optionValue;
            }
            elseif(is_string($options[$optionType]))
            {
                $having_tmp = $options[$optionType];
                $options[$optionType] = array($having_tmp, $optionValue);
            }
            else
            {
                throw new App_Db_Tree_Exception('Ungültiger Inhalt im Options Array! Es sind nur Strings und Arrays erlaubt!');
            }
            
        }
        return $options;
    }
    
    /**
     * Erweitert den übergebenen select um die in Options angegebenen Parameter. 
     * @param Zend_Db_Select $select
     * @param array $options
     * @return Zend_Db_Select
     */
    protected function extendSelect(Zend_Db_Select $select, array $options = null)
    {
        //die auszulesenden spalten um die in $_columns angegebenen spalten erweitern
        if(is_array($this->getColumns()))
        {
            $select->columns($this->getColumns(), self::NODE_ALIAS);
        }
        
        //abbruch, falls keine weiteren optionen angegeben wurden
        if($options == null)
        {
            return $select;
        }
        
        //die ausgelesenen spalten erweitern, falls welche in $options vorhanden
        if(!empty($options[self::COLUMNS]) && is_array($options[self::COLUMNS]))
        {
            $select->columns($options[self::COLUMNS], self::NODE_ALIAS);
        }
        //WHERE clause erweitern, falls ein entsprechender eintrag in $options übergeben wurde
        if(!empty($options[self::WHERE]))
        {
            if(is_string($options[self::WHERE]))
            {
                $select->where($options[self::WHERE]);
            }
            elseif(is_array($options[self::WHERE]))
            {
                foreach($options[self::WHERE] AS $where)
                {
                    $select->where($where);
                }
            }
        }
        //ORDER clause erweitern, falls entsprechender eintrag in $options vorhanden
        if(!empty($options[self::ORDER]))
        {
            if(is_string($options[self::ORDER]))
            {
                $select->order($options[self::ORDER]);
            }
            elseif(is_array($options[self::ORDER]))
            {
                foreach($options[self::ORDER] AS $order)
                {
                    $select->order($order);
                }
            }
        }
        //HAVING teil anhängen, falls entsprechende reintrag in $options vorhanden
        if(!empty($options[self::HAVING]))
        {
            if(is_string($options[self::HAVING]))
            {
                $select->having($options[self::HAVING]);
            }
            elseif(is_array($options[self::HAVING]))
            {
                foreach($options[self::HAVING] AS $having)
                {
                    $select->having($having);
                }
            }
        }
        //LIMIT und OFFSET teil anhängen, falls eintrag in $options vorhanden
        if(!empty($options[self::LIMIT]))
        {
            if(!empty($options[self::OFFSET]))
            {
                $select->limit($options[self::LIMIT], $options[self::OFFSET]);
            }
            else
            {
                $select->limit($options[self::LIMIT]);
            }
        }
        return $select;
    }
    
    /**
     * Setzt den Namen für der Spalte mit dem "right" Wert
     * @param string $rgt Name der Spalte
     * @return App_Db_Tree
     */
    public function setRight($rgt)
    {
        if(is_string($upd))
        {
           $this->_rgt = $rgt;
        }
        else
        {
            throw new App_Db_Tree_Exception('Die Right Spalte kann nicht gesetzt werden,
                                             da kein gültiger String Wert übergeben wurde!');
        }
        return $this;
    }
    
    /**
     * Gibt den Namen der Spalte mit dem "right" Wert zurück
     * @return string
     */
    public function getRight()
    {
        return $this->_rgt;
    }
    
    /**
     * Setzt den Namen für die Spalte mit dem "left" Wert
     * @param string $lft Name der Spalte
     * @return App_Db_Tree
     */
    public function setLeft($lft)
    {
        if(is_string($upd))
        {
           $this->_lft = $lft;
        }
        else
        {
            throw new App_Db_Tree_Exception('Die Left Spalte kann nicht gesetzt werden,
                                             da kein gültiger String Wert übergeben wurde!');
        }
        return $this;
    }
    
    /**
     * Gibt den Namen der Spalte mit dem "left" Wert zurück
     * @return string
     */
    public function getLeft()
    {
        return $this->_lft;
    }
    
    /**
     * Setzt den Namen für die Spalte, in der der temporäre updated Wert gespeichert wird
     * @param string $upd
     * @return App_Db_Tree
     */
    public function setUpdateCol($upd)
    {
        if(is_string($upd))
        {
           $this->_upd = $upd;
        }
        else
        {
            throw new App_Db_Tree_Exception('Die Update Spalte kann nicht gesetzt werden,
                                             da kein gültiger String Wert übergeben wurde!');
        }
        return $this;
    }
    
    /**
     * Gibt den Namen der Spalte, die den temporären Update Wert enthält, zurück
     * @return string
     */
    public function getUpdateCol()
    {
        return $this->_upd;
    }
    
    /**
     * Setzt die Spalten, die zusätzlich zu den standardmäßig ausgelesen Spalten ausgelesen werden
     * @param array $columns
     * @return App_Db_Tree
     */
    public function setColumns(array $columns)
    {
        $this->_columns = $columns;
        return $this;
    }
    
    /**
     * Gibt die Spalten zurück, die zusätzlich zu den standardmäßig ausgelesenen Spalten ausgelesen werden sollen
     * @return unknown_type
     */
    public function getColumns()
    {
        return $this->_columns;
    }
    
    /**
     * Gibt den Primärschlüssen zurück. Der bzw die Primärschlüssel werden in von Zend_Db_Table_Abstract 
     * in einem Array gespeichert (warum auch immer..)
     * @return string
     */
    public function getPrimary()
    {
        if($this->_treePrimary == null)
        {
            $this->setPrimary();
        }
        return $this->_treePrimary;
    }
    
    /**
     * Setzt den Primärschlüssel. Wird aus $this->_primary (von Zend_Db_Table_Abstract) ermittelt.
     * Zend_Db_Table_Abstract speichert den Primary (aus welchem Grund auch immer) in einem Array. Es wird
     * der erste Eintrag aus diesem Array zurückgegeben
     * @return App_Model_Db_Tree
     */
    public function setPrimary()
    {
        $primary = (array) $this->_primary;
        reset($primary);
        $this->_treePrimary = current($primary);
        return $this;
    }
}