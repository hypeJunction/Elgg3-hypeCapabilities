<?php

namespace hypeJunction\Capabilities;

use Elgg\Event;

/**
 * SetRouteMiddleware class.
 */
class SetRouteMiddleware {

	/**
	 * Add role based route access middleware
	 *
	 * @param Event $hook Event
	 * @return array
	 */
	public function __invoke(Event $hook) {

		$config = $hook->getValue();

		$middleware = (array) elgg_extract('middleware', $config);

		array_unshift($middleware, RouteAccessMiddleware::class);

		$config['middleware'] = $middleware;

		return $config;
	}
}
