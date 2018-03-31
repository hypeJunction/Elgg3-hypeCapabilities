<?php

namespace hypeJunction\Capabilities;

use DatabaseException;
use Elgg\Hook;

class SetCreatePermissions {

	/**
	 * Implement role based entity access
	 *
	 * @param Hook $hook Hook
	 *
	 * @return bool|null
	 * @throws DatabaseException
	 */
	public function __invoke(Hook $hook) {

		$container = $hook->getParam('container');
		$user = $hook->getParam('user');

		if (!$container || !$user) {
			return null;
		}

		$svc = elgg()->roles;
		/* @var $svc \hypeJunction\Capabilities\RolesService */

		$roles = $svc->getRolesForPermissionsCheck($user, $container);

		foreach ($roles as $role) {
			$params = $hook->getParams();
			$params['type'] = $hook->getType();

			$rule = $role->getEntityRule(Role::CREATE, $container, $user, $params);
			if ($rule) {
				return $rule->grants($hook->getValue());
			}
		}
	}
}