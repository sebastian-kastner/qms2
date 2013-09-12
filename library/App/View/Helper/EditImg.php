<?php
/**
 * View Helper zur Anzeige von Links zum Bearbeiten von Datensätzen
 * @category   App
 * @package    App_View
 * @subpackage Helper
 * @author     kastners
 */
class App_View_Helper_EditImg extends App_View_Helper_EditImgAbstract
{
    protected $_defaultSrc = '/img/edit.gif';
    protected $_defaultAlt = 'Bearbeiten';
    protected $_defaultTitle = 'Bearbeiten';
    
    public function EditImg($resource, $href, $attributes = null)
    {
        return $this->EditImgAbstract($resource, $href, $attributes);
    }
}