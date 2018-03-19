<?php

namespace hypeJunction\Capabilities;

use Elgg\EntityPermissionsException;
use Elgg\Request;
use RouteAccessCapability;

class RouteAccessMiddleware {

	/**
	 * Request middleware
	 *
	 * @param Request $request Request
	 *
	 * @return void
	 * @throws EntityPermissionsException
	 */
	public function __invoke(Request $request) {

		$params = $request->getParams();

		$container = elgg_get_page_owner_entity() ? : null;
		$user = $request->elgg()->session->getLoggedInUser() ? : null;

		$svc = elgg()->roles;
		/* @var $svc \hypeJunction\Capabilities\RolesService */

		$roles = [];
		if ($container) {
			$roles = $svc->getRoles($user, $container);
		}

		$roles = array_merge($roles, $svc->getRoles($user));
		/* @var $roles \hypeJunction\Capabilities\Role[] */

		foreach ($roles as $role) {
			$rule = $role->getRouteRule($request->getRoute(), $container, $user, $params);
			if ($rule === false) {
				throw new EntityPermissionsException();
			}
		}
	}
}