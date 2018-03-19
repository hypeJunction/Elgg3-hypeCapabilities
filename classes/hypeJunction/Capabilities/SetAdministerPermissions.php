<?php

namespace hypeJunction\Capabilities;

use Elgg\Hook;

class SetAdministerPermissions {

	/**
	 * Implement role based entity access
	 *
	 * @param Hook $hook Hook
	 *
	 * @return bool|null
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
		/* @var $svc \hypeJunction\Capabilities\RolesService */

		$roles = [];

		$container = $entity->getContainerEntity();
		if ($container) {
			$roles = $svc->getRoles($user, $container);
		}

		$roles = array_merge($roles, $svc->getRoles($user));
		/* @var $roles \hypeJunction\Capabilities\Role[] */

		foreach ($roles as $role) {
			$rule = $role->getEntityRule(Role::ADMINISTER, $entity, $user, $hook->getParams());
			if (isset($rule)) {
				return $rule;
			}
		}
	}
}