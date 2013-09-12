<?php
/**
 * View Helper zur Anzeige von Links zum aktivieren von Datensätzen
 * @category   App
 * @package    App_View
 * @subpackage Helper
 * @author     kastners
 */
class App_View_Helper_ActiveImg extends App_View_Helper_EditImgAbstract
{
    /**
     * URI zur Default Bilddatei
     * @var string
     */
    protected $_defaultSrc = '/img/active.gif';
    
    /**
     * Default Wert des Alt Attributs
     * @var string
     */
    protected $_defaultAlt = 'Aktivieren';
    
    /**
     * Default Wert des Title Attributs 
     * @var string
     */
    protected $_defaultTitle = 'Aktivieren';
    
    /**
     * Default Prefix für den ID Tag (wird für Operationen mit jQuery benötigt)
     * @var string
     */
    protected $_idPrefix = 'active_img_';
    
    public function activeImg($resource, $href, $attributes = null, $activeState = 1, $id = null)
    {
        if($activeState == 1)
        {
            $this->_defaultSrc = '/img/active.gif';
            $this->_defaultAlt = 'Zum Deaktivieren klicken';
            $this->_defaultTitle = 'Zum Deaktivieren klicken';
        }
        else
        {
            $this->_defaultSrc = '/img/deactive.gif';
            $this->_defaultAlt = 'Zum Aktivieren klicken';
            $this->_defaultTitle = 'Zum Aktivieren klicken';
        }
        if(!is_array($attributes))
        {
            $attributes = array();
            $attributes['class'] = 'active_img';
            if($id != null)
            {
                $attributes['id'] = $this->_idPrefix.$id;
            }
        }
        else
        {
            if(!array_key_exists('class', $attributes))
            {
                $attributes['class'] = 'active_img';
            }
            if(!array_key_exists('id', $attributes) && $id != null)
            {
                $attributes['id'] = $this->_idPrefix.$id;
            }
        }
        
        return $this->EditImgAbstract($resource, $href, $attributes);
    }
}