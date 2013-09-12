<?php
/**
 * Erzeugt einen Formular Titel
 * @category   App
 * @package    App_Form
 * @subpackage Decorator
 * @author     kastners
 *
 */
class App_Form_Decorator_Title extends Zend_Form_Decorator_Abstract
{  
    /**
     * Erzeugt den output
     * @see library/Zend/Form/Decorator/Zend_Form_Decorator_Abstract#render($content)
     */
    public function render($content)
    {    
        
        $element = $this->getElement();
        if(method_exists($element, "getTitle"))
        {
            $title = trim($element->getTitle());
        }
        else
        {
            $title = trim($element->getDescription());
        }
        
        if($title == "")
        {
            return $content;
        }
        
        $output = "<h1>" . $title . "</h1>";        
        $separator = $this->getSeparator();
        return $output . $separator . $content;
        
    }
}