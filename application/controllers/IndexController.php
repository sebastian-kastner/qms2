<?php

class IndexController extends Zend_Controller_Action
{
    /**
     * Leitet den Benutzer auf eine gewünschte Seite weiter
     * Mögliche Parameter in redirectOptions:
     *      head:           Überschrift
     *      msg:            Text, den der Benutzer zu lesen bekommt
     *      redirectTo:     Pfad, an den weitergeleitet wird (wenn nicht gesetzt erscheint nur der Text, 
     *                      der Benutzer wird aber nicht weitergeleitet)
     *      redirectTime:   Zeit, die bis zur Weiterleitung abgewartet wird
     *      
     * Alle Parameter sind optional. Es sind Standardweter gesetzt.
     * @return void
     */
    public function redirectAction()
    {
        //TODO: eventuell eine möglichkeit implementieren, dass nicht die komplette msg und head übergeben werden müssen
        $redirect = $this->getRequest()->getParam('redirectOptions');
        
        //standardwerte, die überschrieben werden, falls optionen übergeben wurden
        $head = 'Operation durchgef&uuml;hrt';
        $msg = 'Die Operation wurde erfolgreich durchgef&uuml;hrt';
        $redirectTo = null;
        $redirectTime = 4;
        
        //es wurde ein array mit redirect optionen übergeben. die standardwerte werte werden also dementsprechend überschrieben
        if(is_array($redirect))
        {
            $head = (isset($redirect['head'])) ? $redirect['head'] : $head;
            $msg = (isset($redirect['msg'])) ? $redirect['msg'] : $head;
            
            if(array_key_exists('redirect', $redirect))
            {
                $redirectTo = $this->view->baseUrl().$redirect['redirect'];
                $redirectTime = (array_key_exists('redirectTime', $redirect)) ? $redirect['redirectTime'] : $redirectTime;
                
                
                $this->view->headMeta()->appendHttpEquiv('Refresh',
                                                   $redirectTime.";URL=".$redirectTo);
            }
        }       
        
        $this->view->head = $head;
        $this->view->msg = $msg;
        $this->view->redirect = $redirectTo;
    }
    
    public function indexAction()
    {
    	
    }
    
    /**
     * Ändert den Bearbeitungsmodus
     * @return void
     */
    public function seteditmodeAction()
    {
        //Bearbeitungsmodus ändern
        App_EditMode::changeEditMode();
        //weiterleitung auf die ursprungsseite
        $this->_redirect($_SERVER['HTTP_REFERER']);
        //App_Redirector::redirectToPrev();
    }

}

