<?php
/**
 * Überprüft, ob der User die geforderten Rechte hat und sich im Bearbeiten Modus befindet. 
 * Wenn ja, dann wird eine Grafik mit einem Link zum Bearbeiten angezeigt 
 * @category   App
 * @package    App_View
 * @subpackage Helper
 * @see Zend_View_Helper_Abstract 
 */

require_once 'Zend/View/Helper/Abstract.php';

abstract class App_View_Helper_EditImgAbstract extends Zend_View_Helper_Abstract
{
    /**
     * Standard Pfad zur Bilddatei
     * @var string
     */
    protected $_defaultSrc;
    
    /**
     * Standard Alt Tag für das Bild
     * @var string
     */
    protected $_defaultAlt;
    
    /**
     * Standard Titlte Tag für das Bild
     * @var string
     */
    protected $_defaultTitle;
    
    /**
     * CSS Klasse, die für die Bilder angegeben werden kann. 
     * @var string
     */
    protected $_class = null;
    
    /**
     * Target für den Link
     * @var string
     */
    protected $_target = null;
    
    /**
     * Die Identity des Users (von App_Auth)
     * @var mixed 
     */
    protected $_identity;
    
    /**
     * ACL Objekt, um zu überprüfen, ob der Benutzer die geforderten Rechte hat
     * @var Zend_Acl
     */
    protected $_acl;
    
    /**
     * Die Base Url, die der Grafik und den Links vorangestellt wird
     * @var string
     */
    protected $_baseUrl;
    
    protected $_initialized = false;
    
    /**
     * Erzeugt den Link mit der Grafik, falls der User die geforderten Rechte hat. 
     * Vor $href wird standardmäßig die baseUrl gestellt, wenn prependBaseUrl nicht auf false 
     * gesetzt wird 
     * @param string $resource name der resource, für die die rechte überprüft werden sollen
     *                         wird als resource false übergeben wird das bild in jedem fall angezeigt
     *                         (unabhängig von Rechten und vom Bearbeitungsmodus)
     * @param string $href link, auf den das bild verlinkt werden soll
     * @param array $attributes html attribute, die der Bild hinzugefügt werden sollen. Fordert einen 
     *                          assoziativen Array, bei dem der Index dem Attributnamen und der Wert 
     *                          dem Attributwert entsprechen. Um dem Bild einen title zu geben würde man
     *                          folgenden array übergeben: array('title' => 'Bildtitel')
     */
    public function EditImgAbstract($resource, $href, $attributes = null)
    {
        if(!$this->_initialized)
        {
            $this->_identity = App_Auth::getInstance()->getIdentity();
            $this->_acl = Zend_Registry::get('acl');
            $this->baseUrl = $this->view->baseUrl();
            $this->_defaultSrc = $this->baseUrl.$this->_defaultSrc;
            $this->_initialized = true;
        }
        
        //TODO: ACL CHECKEN! WENN KEINE RECHTE -> RETURN "";
        
        if(!App_EditMode::isEditMode() && $resource != false)
        {
            return '';
        }
        
        $href = ($href != '#') ? $this->baseUrl."/".$href : '#';
        $alt = $this->_defaultAlt;
        $title = $this->_defaultTitle;
        $src = (substr($this->_defaultSrc, 0, strlen($this->baseUrl)) == $this->baseUrl) 
                   ? $this->_defaultSrc
                   : $this->baseUrl.$this->_defaultSrc;
        $target = '';
        $style = 'border:0px;';
        $class = '';
        
        if(is_array($attributes))
        {
           if(array_key_exists('alt', $attributes))
           {
               $alt = $attributes['alt'];
               unset($attributes['alt']);
           }
           if(array_key_exists('title', $attributes))
           {
               $title = $attributes['title'];
               unset($attributes['title']);
           }
           if(array_key_exists('src', $attributes))
           {
               $src = $this->baseUrl."/".$attributes['src'];
               unset($attributes['src']);
           }
           if(array_key_exists('target', $attributes))
           {
               $target = " target=\"".$attributes['target']."\"";
               unset($attributes['target']);
           }
           if(array_key_exists('class', $attributes))
           {
               $class = " class=\"".$attributes['class']."\" ";
               unset($attributes['class']);
           }
           if(array_key_exists('style', $attributes))
           {
                  if(strpos($attributes['style'], 'border'))
                  {
                         $style = $attributes['style'];
                  }
                  else
                  {
                         $style = $attributes['style'];
                         $style .= (substr($style, -1, 1) != ';') ? ';' : '';
                         $style .= (stripos($style, 'border')) ? '' : "border:0px;";
                  }
                  unset($attributes['style']);
           }
        }
                
        $a = "<a href=\"".$href."\"".$target.">";
        $img = "<img src=\"".$src."\" alt=\"".$alt."\" title=\"".$title."\"".$class."style=\"".$style."\"";
        
        if(is_array($attributes))
        {
            foreach($attributes AS $att => $value)
            {
               $img .= " ".$att."=\"".$value."\"";    
            }
        }
        
        $img .= " />";
        $html = $a.$img."</a>";
        return $html;
    }
}
