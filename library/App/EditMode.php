<?php

/**
 * Dient mit statischen Methoden zum Setzen und Auslesen des Bearbeitungsmodus.
 * Der Bearbeitungsmodus wird als BOOLEAN gespeichert. Befindet der User sich aktuell im Bearbeitungsmodus
 * ist der der $_editMode folglich TRUE, wenn nicht FALSE
 *
 * @category   App
 * @package    App_View
 * @subpackage Helper
 * @author     kastners
 */
class App_EditMode
{
    /**
     * Aktuelle Session
     * @var Zend_Session_Namespace
     */
    protected static $_session = null;
    
    /**
     * Namespace der Session, in der der Bearbeitungsmodus gespeichert wird
     * @var string
     */
    protected static $_sessionNamespace = 'editMode';
    
    /**
     * Gibt zurück, ob der Benutzer sich momentan im Bearbeitungsmodus befindet oder nicht
     * @return bool
     */
    public static function isEditMode()
    {
        $session = self::getSession();
        return (empty($session->editMode)) ? false : $session->editMode;
    }
    
    /**
     * Setzt den Bearbeitungsmodus und speichert ihn in der Session
     * @param bool $flag
     * @return void
     */
    public static function setEditMode($flag)
    {
        if(is_bool($flag))
        {
            $session = self::getSession();
            $session->unlock();
            $session->editMode = $flag;
        }
    }
    
    /**
     * Ändert den Bearbeitungsmodus. Stellt von FALSE auf TRUE bzw von TRUE auf FALSE um.
     * @return void
     */
    public static function changeEditMode()
    {
        if(self::isEditMode())
        {
            self::setEditMode(false);
        }
        else
        {
            self::setEditMode(true);
        }
    }
    
    /**
     * Gibt die aktuelle Session zurück
     * @return Zend_Session_Namespace
     */
    public static function getSession()
    {
        if(self::$_session== null)
        {
            $session = new Zend_Session_Namespace(self::$_sessionNamespace);
            self::setSession($session);
        }
        return self::$_session;
    }
    
    /**
     * Setzt die aktuelle Session
     * @param Zend_Session_Namespace $session
     * @return void
     */
    public static function setSession($session)
    {
        self::$_session = $session;
    }
}

?>