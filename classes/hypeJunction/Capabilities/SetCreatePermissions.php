<?php

namespace hypeJunction\Capabilities;

use DatabaseException;
use Elgg\Event;

/**
 * SetCreatePermissions class.
 */
class SetCreatePermissions {

	/**
	 * Implement role based entity access
	 *
	 * @param Event $event Event
	 *
	 * @return bool|null
	 * @throws DatabaseException
	 */
	public function __invoke(Event $event) {

		$container = $event->getParam('container');
		$user = $event->getParam('user');

		if (!$container || !$user) {
			return null;
		}

		$svc = elgg()->roles;
		/* @var $svc \hypeJunction\Capabilities\Roles */

		$roles = $svc->getRolesForPermissionsCheck($user, $container);

		foreach ($roles as $role) {
			$params = $event->getParams();
			$params['type'] = $event->getType();

			$rule = $role->getEntityRule(Role::CREATE, $container, $user, $params);
			if ($rule) {
				return $rule->grants($event->getValue());
			}
		}
	}
}
