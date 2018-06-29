<?php

namespace hypeJunction\Capabilities;

use DatabaseException;
use Elgg\Hook;

class SetCustomPermissions {

	/**
	 * Implement custom capability permission
	 *
	 * @param Hook $hook Hook
	 *
	 * @return bool|null
	 * @throws DatabaseException
	 */
	public function __invoke(Hook $hook) {
		$user = $hook->getParam('user');
		$container = $hook->getParam('container');
		$entity = $hook->getParam('entity');
		$action = $hook->getParam('action');
		$component = $hook->getParam('component');

		if (!$user) {
			return null;
		}

		$svc = Roles::instance();

		$roles = $svc->getRolesForPermissionsCheck($user, $container);
		/* @var $roles \hypeJunction\Capabilities\Role[] */

		foreach ($roles as $role) {
			$rule = $role->getCustomRule($action, $component, $entity, $user, $hook->getParams());
			if ($rule) {
				return $rule->grants($hook->getValue());
			}
		}
	}
}