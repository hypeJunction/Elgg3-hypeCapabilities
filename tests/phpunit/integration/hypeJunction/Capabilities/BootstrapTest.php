<?php

namespace hypeJunction\Capabilities;

use Elgg\IntegrationTestCase;

/**
 * Characterization suite for hypecapabilities on Elgg 4.x.
 *
 * Role-based permission framework: the plugin wires seven permission/
 * menu hook handlers at init time and binds a 'roles' service on the
 * Elgg DI container via elgg-services.php. Test surface covers plugin
 * lifecycle, class/interface autoloading, the DI-services binding, and
 * each of the seven Bootstrap::init hook registrations.
 */
class BootstrapTest extends IntegrationTestCase {

	public function getPluginID(): string {
		return 'hypecapabilities';
	}

	public function up() {}
	public function down() {}

	// --- plugin lifecycle ---

	public function testPluginIsRegistered() {
		$this->assertInstanceOf(\ElggPlugin::class, elgg_get_plugin_from_id('hypecapabilities'));
	}

	public function testPluginIsActive() {
		$this->assertTrue(elgg_get_plugin_from_id('hypecapabilities')->isActive());
	}

	// --- class + interface autoloading ---

	public function testBootstrapClassLoads() {
		$this->assertTrue(class_exists(Bootstrap::class));
	}

	public function testRolesServiceClassLoads() {
		$this->assertTrue(class_exists(Roles::class));
	}

	public function testRoleClassLoads() {
		$this->assertTrue(class_exists(Role::class));
	}

	public function testRoleInterfaceLoads() {
		$this->assertTrue(interface_exists(RoleInterface::class));
	}

	public function testRuleClassLoads() {
		$this->assertTrue(class_exists(Rule::class));
	}

	public function testRuleInterfaceLoads() {
		$this->assertTrue(interface_exists(RuleInterface::class));
	}

	public function testContextClassLoads() {
		$this->assertTrue(class_exists(Context::class));
	}

	public function testContextInterfaceLoads() {
		$this->assertTrue(interface_exists(ContextInterface::class));
	}

	public function testSetReadPermissionsLoads() {
		$this->assertTrue(class_exists(SetReadPermissions::class));
	}

	public function testSetEditPermissionsLoads() {
		$this->assertTrue(class_exists(SetEditPermissions::class));
	}

	public function testSetDeletePermissionsLoads() {
		$this->assertTrue(class_exists(SetDeletePermissions::class));
	}

	public function testSetAdministerPermissionsLoads() {
		$this->assertTrue(class_exists(SetAdministerPermissions::class));
	}

	public function testSetCreatePermissionsLoads() {
		$this->assertTrue(class_exists(SetCreatePermissions::class));
	}

	public function testSetCustomPermissionsLoads() {
		$this->assertTrue(class_exists(SetCustomPermissions::class));
	}

	public function testPrepareMenusLoads() {
		$this->assertTrue(class_exists(PrepareMenus::class));
	}

	public function testRouteAccessMiddlewareLoads() {
		$this->assertTrue(class_exists(RouteAccessMiddleware::class));
	}

	// --- elgg-services.php DI binding (the DI\object → DI\create fix) ---

	public function testRolesServiceIsBoundOnElggContainer() {
		$this->assertTrue(elgg()->has('roles'));
		$this->assertInstanceOf(Roles::class, elgg()->roles);
	}

	// --- Bootstrap::init hook wiring (7 handlers) ---

	public function testGatekeeperHookWired() {
		$handlers = _elgg_services()->hooks->getAllHandlers();
		$this->assertArrayHasKey('gatekeeper', $handlers);
		$this->assertArrayHasKey('all', $handlers['gatekeeper']);
	}

	public function testPermissionsCheckHookWired() {
		$handlers = _elgg_services()->hooks->getAllHandlers();
		$this->assertArrayHasKey('permissions_check', $handlers);
		$this->assertArrayHasKey('all', $handlers['permissions_check']);
	}

	public function testPermissionsCheckDeleteHookWired() {
		$handlers = _elgg_services()->hooks->getAllHandlers();
		$this->assertArrayHasKey('permissions_check:delete', $handlers);
	}

	public function testPermissionsCheckAdministerHookWired() {
		$handlers = _elgg_services()->hooks->getAllHandlers();
		$this->assertArrayHasKey('permissions_check:administer', $handlers);
	}

	public function testContainerPermissionsCheckHookWired() {
		$handlers = _elgg_services()->hooks->getAllHandlers();
		$this->assertArrayHasKey('container_permissions_check', $handlers);
		$this->assertArrayHasKey('all', $handlers['container_permissions_check']);
	}

	public function testCapabilityHookWired() {
		$handlers = _elgg_services()->hooks->getAllHandlers();
		$this->assertArrayHasKey('capability', $handlers);
		$this->assertArrayHasKey('all', $handlers['capability']);
	}

	public function testPrepareMenuHookWired() {
		$handlers = _elgg_services()->hooks->getAllHandlers();
		$this->assertArrayHasKey('prepare', $handlers);
		$this->assertArrayHasKey('all', $handlers['prepare']);
	}
}
