<?php

namespace hypeJunction\Capabilities;

use DatabaseException;
use Elgg\Event;

/**
 * SetCustomPermissions class.
 */
class SetCustomPermissions {

	/**
	 * Implement custom capability permission
	 *
	 * @param Event $event Event
	 *
	 * @return bool|null
	 * @throws DatabaseException
	 */
	public function __invoke(Event $event) {
		$user = $event->getParam('user');
		$container = $event->getParam('container');
		$entity = $event->getParam('entity');
		$action = $event->getParam('action');
		$component = $event->getParam('component');

		if (!$user) {
			return null;
		}

		$svc = Roles::instance();

		$roles = $svc->getRolesForPermissionsCheck($user, $container);
		/* @var $roles \hypeJunction\Capabilities\Role[] */

		foreach ($roles as $role) {
			$rule = $role->getCustomRule($action, $component, $entity, $user, $event->getParams());
			if ($rule) {
				return $rule->grants($event->getValue());
			}
		}
	}
}
