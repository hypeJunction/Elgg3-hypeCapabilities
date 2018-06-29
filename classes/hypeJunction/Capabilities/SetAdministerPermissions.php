<?php

namespace hypeJunction\Capabilities;

use DatabaseException;
use Elgg\Hook;

class SetAdministerPermissions {

	/**
	 * Implement role based entity access
	 *
	 * @param Hook $hook Hook
	 *
	 * @return bool|null
	 * @throws DatabaseException
	 */
	public function __invoke(Hook $hook) {

		$entity = $hook->getEntityParam();
		$user = $hook->getParam('user');

		if (!$entity || !$user) {
			return null;
		}

		if ($user->isAdmin()) {
			return true;
		}

		$svc = elgg()->roles;
		/* @var $svc \hypeJunction\Capabilities\Roles */

		$container = $entity->getContainerEntity();

		$roles = $svc->getRolesForPermissionsCheck($user, $container);
		/* @var $roles \hypeJunction\Capabilities\Role[] */

		foreach ($roles as $role) {
			$rule = $role->getEntityRule(Role::ADMINISTER, $entity, $user, $hook->getParams());
			if ($rule) {
				return $rule->grants($hook->getValue());
			}
		}
	}
}