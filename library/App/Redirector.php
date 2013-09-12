<?php
/**
 * Klasse für Weiterleitungen innerhalb des Projekts
 * 
 * @author kastners
 * @category App
 * @package App_Redirector
 */
class App_Redirector
{
    /**
     * Leitet den Benutzer auf die Herkunftsseite weiter (zb nach dem Login)
     */
    public static function redirectToPrev()
    {
        header('Location: '.$_SERVER["HTTP_REFERER"]);
    }
}