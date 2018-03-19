<?php

use hypeJunction\Capabilities\Role;

require_once __DIR__ . '/autoloader.php';

return function () {

	elgg_register_plugin_hook_handler('route:config', 'all', \hypeJunction\Capabilities\SetRouteMiddleware::class);

	elgg_register_event_handler('init', 'system', function () {

		elgg_register_plugin_hook_handler('gatekeeper', 'all', \hypeJunction\Capabilities\SetReadPermissions::class);
		elgg_register_plugin_hook_handler('permissions_check', 'all', \hypeJunction\Capabilities\SetEditPermissions::class);
		elgg_register_plugin_hook_handler('permissions_check:delete', 'all', \hypeJunction\Capabilities\SetDeletePermissions::class);
		elgg_register_plugin_hook_handler('permissions_check:administer', 'all', \hypeJunction\Capabilities\SetAdministerPermissions::class);
		elgg_register_plugin_hook_handler('container_permissions_check', 'all', \hypeJunction\Capabilities\SetCreatePermissions::class);
		elgg_register_plugin_hook_handler('prepare', 'all', \hypeJunction\Capabilities\PrepareMenus::class);
	});
};
