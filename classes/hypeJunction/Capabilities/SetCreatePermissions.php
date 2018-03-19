<?php

namespace hypeJunction\Capabilities;

use Elgg\Hook;

class SetCreatePermissions {

	/**
	 * Implement role based entity access
	 *
	 * @param Hook $hook Hook
	 *
	 * @return bool|null
	 */
	public function __invoke(Hook $hook) {

		$container = $hook->getParam('container');
		$user = $hook->getParam('user');

		if (!$container || !$user) {
			return null;
		}

		$svc = elgg()->roles;
		/* @var $svc \hypeJunction\Capabilities\RolesService */

		$roles = $svc->getRoles($user, $container);
		$roles = array_merge($roles, $svc->getRoles($user));
		/* @var $roles \hypeJunction\Capabilities\Role[] */

		foreach ($roles as $role) {
			$rule = $role->getEntityRule(Role::CREATE, $container, $user, $hook->getParams());
			if (isset($rule)) {
				return $rule;
			}
		}
	}
}