<?php

namespace hypeJunction\Capabilities;

use Elgg\Event;

/**
 * SetEditPermissions class.
 */
class SetEditPermissions {

	/**
	 * Implement role based entity access
	 *
	 * @param Event $event Event
	 *
	 * @return bool|null
	 * @throws \DatabaseException
	 */
	public function __invoke(Event $event) {

		$entity = $event->getEntityParam();
		$user = $event->getParam('user');

		if (!$entity || !$user) {
			return null;
		}

		$svc = elgg()->roles;
		/* @var $svc \hypeJunction\Capabilities\Roles */

		$container = $entity->getContainerEntity();

		$roles = $svc->getRolesForPermissionsCheck($user, $container);

		foreach ($roles as $role) {
			$rule = $role->getEntityRule(Role::UPDATE, $entity, $user, $event->getParams());
			if ($rule) {
				return $rule->grants($event->getValue());
			}
		}

		foreach ($roles as $role) {
			$rule = $role->getEntityRule(Role::ADMINISTER, $entity, $user, $event->getParams());
			if ($rule) {
				$grant = $rule->grants($event->getValue());
				if ($grant === true) {
					return true;
				}
			}
		}
	}
}
