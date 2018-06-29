<?php

namespace hypeJunction\Capabilities;

use Elgg\Hook;

class PrepareMenus {

	/**
	 * Remove menu items user can't access
	 *
	 * @param Hook $hook Hook
	 *
	 * @return array|null
	 */
	public function __invoke(Hook $hook) {

		$sections = $hook->getValue();

		$hook_type = explode(':', $hook->getType());

		if ($hook_type[0] !== 'menu') {
			return null;
		}

		$user = elgg_get_logged_in_user_entity();

		foreach ($sections as $section => $items) {
			foreach ($items as $key => $item) {
				/* @var $item \ElggMenuItem */

				$href = $item->getHref();
				$href = elgg_normalize_url($href);

				$site_url = elgg_get_site_url();

				if (strpos($href, $site_url) !== 0) {
					continue;
				}

				$path = substr($href, strlen($site_url));
				$path = '/' . $path;

				try {
					$params = _elgg_services()->urlMatcher->match($path);

					$route = elgg_extract('_route', $params);

					$container = $this->resolveTarget($route, $params) ? : null;

					$svc = elgg()->roles;
					/* @var $svc \hypeJunction\Capabilities\Roles */

					$roles = $svc->getRolesForPermissionsCheck($user, $container);

					foreach ($roles as $role) {
						$rule = $role->getRouteRule($route, $container, $user, $params);
						if ($rule) {
							$grant = $rule->grants(true);
							if ($grant === false) {
								unset($sections[$section][$key]);
							}
						}
					}
				} catch (\Exception $ex) {

				}
			}

			return $sections;
		}


	}

	/**
	 * Resolve container entity
	 *
	 * @param string $route  Route name
	 * @param array  $params Matched parameters
	 *
	 * @return \ElggEntity|\ElggUser|false
	 */
	protected function resolveTarget($route, array $params = []) {
		$route_parts = explode(':', $route);

		$from_guid = function ($guid) {
			$entity = get_entity($guid);
			if ($entity instanceof \ElggUser || $entity instanceof \ElggGroup) {
				return $entity;
			} else if ($entity instanceof \ElggObject) {
				return $entity->getContainerEntity();
			}
		};

		switch ($route_parts[0]) {
			case 'view' :
			case 'edit' :
				$username = elgg_extract('username', $params);
				if ($username) {
					return get_user_by_username($username);
				}

				$guid = elgg_extract('guid', $params);
				if ($guid) {
					return $from_guid($guid);
				}
				break;

			case 'add' :
			case 'collection' :
				$username = elgg_extract('username', $params);
				if ($username) {
					return get_user_by_username($username);
				}

				$guid = elgg_extract('guid', $params);
				if ($guid) {
					return $from_guid($guid);
				}

				$container_guid = elgg_extract('container_guid', $params);
				if ($container_guid) {
					return $from_guid($container_guid);
				}
				break;
		}
	}
}