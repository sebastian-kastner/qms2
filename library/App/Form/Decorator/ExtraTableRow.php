<?php
/**
 * Fügt vor bzw nach dem Formularfeld eine neue Tabellenzeile mit x Spalten ein
 * @category   App
 * @package    App_Form
 * @subpackage Decorator
 * @author     kastners
 *
 */
class App_Form_Decorator_ExtraTableRow extends Zend_Form_Decorator_Abstract
{   
    /**
     * @var string Wird der erzeugte text/html code dem bestehenden inhalt vor- oder nachgestellt?
     */
    protected $_placement = 'PREPEND';
    
    /**
     * Erzeugt den output
     * @see library/Zend/Form/Decorator/Zend_Form_Decorator_Abstract#render($content)
     */
    public function render($content)
    {
        $placement = $this->getPlacement();
        $cols = $this->getOption('cols');
        if(!is_array($cols))
        {
            throw new App_Form_Exception('Es wurden keine gültigen Werte für die einzufügende Tabellenzeile übergeben!');
        }
        
        //output starten
        $output = "<tr class=\"table_head\">";
        //erste spalte bleibt leer (spalte mit den "überschriften)
        $output .= "  <td></td>";
        foreach($cols AS $col)
        {
            $output .= "  <td>".$col."</td>";
        }
        $output .= "</tr>";
        
        switch ($placement)
        {
            case 'APPEND':
                return $content . $output;
            case 'PREPEND':
            default:
                return $output . $content;
        } 
    }
}