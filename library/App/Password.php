<?php
/**
 * Klasse mit statischer Funktion zum Hashen von Benutzerpasswörtern
 * 
 * @author kastners
 * @category App
 * @package App_Password
 */
class App_Password
{
    public static $_salt = "";
    public static $_saltPos = self::APPEND;
    
    
    const PREPEND = "prepend";
    const APPEND = "append";
    /**
     * Hasht ein Passwort mit dem gesetzten salt mit SHA1
     * @param $password
     * @return string
     */
    public static function hash($password)
    {
        $password = (self::$_saltPos == self::APPEND) ? self::$_salt . $password : $password . self::$_salt;
        return sha1($password);
    }
}