<?php

namespace hypeJunction\Capabilities;

use Elgg\Hook;

class SetRouteMiddleware {

	/**
	 * Add role based route access middleware
	 *
	 * @param Hook $hook Hook
	 * @return array
	 */
	public function __invoke(Hook $hook) {

		$config = $hook->getValue();

		$middleware = (array) elgg_extract('middleware', $config);

		array_unshift($middleware, RouteAccessMiddleware::class);

		$config['middleware'] = $middleware;

		return $config;
	}
}