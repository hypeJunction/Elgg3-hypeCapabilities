<?php

namespace hypeJunction\Capabilities;

use DatabaseException;
use Elgg\EntityPermissionsException;
use Elgg\Request;

class RouteAccessMiddleware {

	/**
	 * Request middleware
	 *
	 * @param Request $request Request
	 *
	 * @return void
	 * @throws EntityPermissionsException
	 * @throws DatabaseException
	 */
	public function __invoke(Request $request) {

		$params = $request->getParams();

		$container = elgg_get_page_owner_entity() ? : null;
		$user = $request->elgg()->session->getLoggedInUser() ? : null;

		$svc = elgg()->roles;
		/* @var $svc \hypeJunction\Capabilities\Roles */

		$roles = $svc->getRolesForPermissionsCheck($user, $container);

		foreach ($roles as $role) {
			$rule = $role->getRouteRule($request->getRoute(), $container, $user, $params);
			if ($rule) {
				$grant = $rule->grants(true);
				if ($grant === false) {
					throw new EntityPermissionsException();
				}
			}
		}
	}
}