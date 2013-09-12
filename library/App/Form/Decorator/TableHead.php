<?php
/**
 * Erzeugt einen Tabellenkopf für das Formular
 * @category   App
 * @package    App_Form
 * @subpackage Decorator
 * @author     kastners
 *
 */
class App_Form_Decorator_TableHead extends App_Form_Decorator_Title
{  
    /**
     * Erzeugt den Output
     * @see library/App/Form/Decorator/App_Form_Decorator_Title#render($content)
     */
    public function render($content)
    {
        $firstTrPos = strpos($content, '<tr>');
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
        
        $output = "<thead><tr><td colspan=\"2\">".$title."</td></tr></thead>";
        return substr($content, 0, ($firstTrPos-1)). $output . substr($content, $firstTrPos);
    }
}