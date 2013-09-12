<?php
/**
 * View Helper zur Anzeige von Links zum Löschen von Datensätzen
 * @category   App
 * @package    App_View
 * @subpackage Helper
 * @author     kastners
 */
class App_View_Helper_DelImg extends App_View_Helper_EditImgAbstract
{
    protected $_defaultSrc = '/img/del.gif';
    protected $_defaultAlt = 'Löschen';
    protected $_defaultTitle = 'Löschen';
    
    public function DelImg($resource, $href, $attributes = null)
    {
        return $this->EditImgAbstract($resource, $href, $attributes);
    }
}