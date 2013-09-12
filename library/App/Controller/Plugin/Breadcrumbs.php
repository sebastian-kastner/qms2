<?php
/**
 * Erstellt zu Beginn der Programmlaufzeit die Breadcrumbs für die Navigation
 * @category   App
 * @package    App_Controller
 * @subpackage Plugins
 * @author     kastners
 *
 */
class App_Controller_Plugin_Breadcrumbs extends Zend_Controller_Plugin_Abstract
{
	public function dispatchLoopStartup()
	{
		$view = Zend_Layout::getMvcInstance()->getView();
		$request = $this->getRequest();
		$module = $request->getModuleName();

		$config = new Zend_Config_Xml(APPLICATION_PATH . "/configs/nav.xml", 'nav');

		$container = new Zend_Navigation($config);
		$view->navigation($container);
		$auth = App_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$identity = $auth->getIdentity();
			$role = $identity['user_id'];
		}
		else
		{
			$role = App_Acl::getGuestRole();
		}
		$acl = Zend_Registry::get('acl');
		$view->navigation()->setAcl($acl)->setRole($role);

		$modulePage = $view->navigation()->menu()->findById($module);
		
		//oberpunkt setzen
		$view->moduleName = $modulePage->getLabel();

		if($module == "admin")
		{
			//$view->navigation()->menu()->setMaxDepth(2)->setMinDepth(1)->setOnlyActiveBranch(true);
			//$view->menu = $view->navigation()->menu()->render();
			$view->menu = $view->navigation()->menu()->setMaxDepth(1)->render($modulePage);
		}
		elseif($module == "process")
		{
			$view->menu = $view->navigation()->menu()->render($modulePage);
		}
		else
		{
			$view->navigation()->menu()->setMaxDepth(0)->setMinDepth(0);
			$view->menu = $view->navigation()->menu()->render();
		}

		//standardeinstellungen für die breadcrumbs setzen
		$view->navigation()->breadcrumbs()->setLinkLast(false);
	}
}