<?php
class Admin_IndexController extends Zend_Controller_Action
{    
    public function indexAction()
    {
        $this->view->navigation()->menu()->setMinDepth(0)->setMaxDepth(1);
    }    
}