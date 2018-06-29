<?php

namespace hypeJunction\Capabilities;

use Elgg\Includer;
use Elgg\PluginBootstrap;
use hypeJunction\Capabilities\PrepareMenus;
use hypeJunction\Capabilities\SetAdministerPermissions;
use hypeJunction\Capabilities\SetCreatePermissions;
use hypeJunction\Capabilities\SetDeletePermissions;
use hypeJunction\Capabilities\SetEditPermissions;
use hypeJunction\Capabilities\SetReadPermissions;

class Bootstrap extends PluginBootstrap {

	/**
	 * Get plugin root
	 * @return string
	 */
	protected function getRoot() {
		return $this->plugin->getPath();
	}

	/**
	 * {@inheritdoc}
	 */
	public function load() {
		Includer::requireFileOnce($this->getRoot() . '/autoloader.php');
	}

	/**
	 * {@inheritdoc}
	 */
	public function boot() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function init() {

		elgg_register_plugin_hook_handler('gatekeeper', 'all', SetReadPermissions::class);
		elgg_register_plugin_hook_handler('permissions_check', 'all', SetEditPermissions::class);
		elgg_register_plugin_hook_handler('permissions_check:delete', 'all', SetDeletePermissions::class);
		elgg_register_plugin_hook_handler('permissions_check:administer', 'all', SetAdministerPermissions::class);
		elgg_register_plugin_hook_handler('container_permissions_check', 'all', SetCreatePermissions::class);
		elgg_register_plugin_hook_handler('capability', 'all', SetCustomPermissions::class);
		elgg_register_plugin_hook_handler('prepare', 'all', PrepareMenus::class);
	}

	/**
	 * {@inheritdoc}
	 */
	public function ready() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function shutdown() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function activate() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function deactivate() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function upgrade() {

	}

}