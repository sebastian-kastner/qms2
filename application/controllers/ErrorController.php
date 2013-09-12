<?php

class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        $this->view->exception = $errors->exception;
        $this->view->request   = $errors->request;
        switch ($errors->type) { 
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
                
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
        
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'Page not found';
                break;

            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
                fbLog(get_class($errors->exception));
                switch (get_class($errors->exception)) {
                    case 'Zend_Db_Statement_Exception':
                        $this->view->details = Zend_Registry::get('db')->getProfiler()->getLastQueryProfile();    
                        break;
                    case 'App_Auth_Exception':
                        $this->render('authorization-failed');
                        break;
                    case 'App_Acl_Exception':
                        $this->render('not-authorized');
                        break;
                    default:
                        $this->getResponse()->setHttpResponseCode(500);
                        $this->view->message = 'Application error';
                        break;
                }
            break;
            
            default:
                // application error 
                $this->getResponse()->setHttpResponseCode(500);
                //$this->view->message = 'Application error';
                $this->view->message = $errors->exception->getMessage();
                break;
        }
        
    }


}

