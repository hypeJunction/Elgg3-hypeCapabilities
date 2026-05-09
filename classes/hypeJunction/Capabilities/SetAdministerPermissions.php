<?php

namespace hypeJunction\Capabilities;

use DatabaseException;
use Elgg\Event;

/**
 * SetAdministerPermissions class.
 */
class SetAdministerPermissions {

	/**
	 * Implement role based entity access
	 *
	 * @param Event $event Event
	 *
	 * @return bool|null
	 * @throws DatabaseException
	 */
	public function __invoke(Event $event) {

		$entity = $event->getEntityParam();
		$user = $event->getParam('user');

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
			$rule = $role->getEntityRule(Role::ADMINISTER, $entity, $user, $event->getParams());
			if ($rule) {
				return $rule->grants($event->getValue());
			}
		}
	}
}
