<?php
/**
 * QMS 2.0 spezifische Erweiterungen für die Zend_Acl Komponente
 * @category   App
 * @package    App_Acl
 * @author     kastners
 */
class App_Auth extends Zend_Auth
{
	/**
	 * Rolle des aktuell eingeloggten Benutzers
	 * @var String
	 */
	static protected $_currentUserRole = null;

	public static function getUserRole()
	{
		if(self::$_currentUserRole == null)
		{
			self::_setUserRole();
		}
		return self::$_currentUserRole;
	}

	/**
	 * Liest die Rolle des aktuellen Benutzers aus und speichert sie im Objekt
	 */
	protected function _setUserRole()
	{
		$identity = self::getInstance()->getIdentity();
		if($identity)
		{
			self::$_currentUserRole = $identity['user_id'];
		}
		else
		{
			self::$_currentUserRole = App_Acl::getRoleId(App_Acl::$GUEST_ROLE_ID);
		}
	}
}