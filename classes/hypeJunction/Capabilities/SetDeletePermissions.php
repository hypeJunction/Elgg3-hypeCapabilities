<?php

namespace hypeJunction\Capabilities;

use DatabaseException;
use Elgg\Hook;

class SetDeletePermissions {

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

		$svc = elgg()->roles;
		/* @var $svc \hypeJunction\Capabilities\Roles */

		$container = $entity->getContainerEntity();

		$roles = $svc->getRolesForPermissionsCheck($user, $container);

		foreach ($roles as $role) {
			$rule = $role->getEntityRule(Role::DELETE, $entity, $user, $hook->getParams());
			if ($rule) {
				return $rule->grants($hook->getValue());
			}
		}
	}
}