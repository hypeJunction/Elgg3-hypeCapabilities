<?php

namespace hypeJunction\Capabilities;

use Elgg\IntegrationTestCase;

/**
 * Behavior tests for permission handlers, route middleware, and views.
 * Rule / Role / Roles service unit tests live in RolesTest.php.
 */
class CapabilitiesTest extends IntegrationTestCase {

	public function getPluginID(): string {
		return 'hypecapabilities';
	}

	public function up() {}

	public function down() {}

	// -------------------------------------------------------------------------
	// Rule — callable condition (unique to this file)
	// -------------------------------------------------------------------------

	public function testRuleCallableConditionReceivesContextAndRule(): void {
		$received = [];
		$callable = function (Context $ctx, Rule $rule) use (&$received) {
			$received['context'] = $ctx;
			$received['rule']    = $rule;
			return true;
		};

		$rule = new Rule(null, $callable);

		$user   = $this->createUser();
		$entity = $this->createObject(['subtype' => 'testobj', 'owner_guid' => $user->guid]);
		$rule->setContext(new Context($entity, $user));

		$this->assertTrue($rule->grants(false));
		$this->assertSame($rule, $received['rule']);
		$this->assertInstanceOf(Context::class, $received['context']);
	}

	public function testRuleCallableConditionCanReturnFalse(): void {
		$rule = new Rule(null, function () { return false; });
		$rule->setContext(new Context());
		$this->assertFalse($rule->grants(true));
	}

	// -------------------------------------------------------------------------
	// Role — getEntityRule / getRouteRule / getCustomRule with real entities
	// -------------------------------------------------------------------------

	public function testRoleGetEntityRuleReturnsNullForUnregisteredType(): void {
		$role   = new Role('myrole');
		$entity = $this->createObject(['subtype' => 'testobj']);
		$this->assertNull($role->getEntityRule(Role::READ, $entity));
	}

	public function testRoleOnReadRegistersCapabilityAndGetEntityRuleReturnsIt(): void {
		$role   = new Role('myrole');
		$entity = $this->createObject(['subtype' => 'testobj']);
		$role->onRead('object', 'testobj', Role::ALLOW);
		$result = $role->getEntityRule(Role::READ, $entity);
		$this->assertInstanceOf(Rule::class, $result);
		$this->assertTrue($result->grants(null));
	}

	public function testRoleGetEntityRuleInheritsFromExtendedRole(): void {
		$svc = elgg()->roles;
		$svc->register('parent_role');
		$svc->parent_role->onRead('object', 'inheritobj', Role::ALLOW);
		$svc->register('child_role', ['parent_role']);

		$entity = $this->createObject(['subtype' => 'inheritobj']);
		$result = $svc->child_role->getEntityRule(Role::READ, $entity);
		$this->assertInstanceOf(Rule::class, $result);
		$this->assertTrue($result->grants(null));

		$svc->unregister('child_role');
		$svc->unregister('parent_role');
	}

	public function testRoleOnRouteAccessRegistersRouteRule(): void {
		$role = new Role('myrole');
		$role->onRouteAccess('view:myroute', Role::DENY);
		$result = $role->getRouteRule('view:myroute');
		$this->assertInstanceOf(Rule::class, $result);
		$this->assertFalse($result->grants(true));
	}

	public function testRoleGetRouteRuleReturnsNullForUnregisteredRoute(): void {
		$role = new Role('myrole');
		$this->assertNull($role->getRouteRule('view:no_such_route'));
	}

	public function testRoleOnRegistersCustomCapabilityAndGetCustomRuleReturnsIt(): void {
		$role = new Role('myrole');
		$role->on('custom_action', 'my_component', Role::ALLOW);
		$result = $role->getCustomRule('custom_action', 'my_component');
		$this->assertInstanceOf(Rule::class, $result);
		$this->assertTrue($result->grants(null));
	}

	public function testRoleGetCustomRuleReturnsNullForUnregisteredComponent(): void {
		$role = new Role('myrole');
		$this->assertNull($role->getCustomRule('custom_action', 'no_such_component'));
	}

	// -------------------------------------------------------------------------
	// SetAdministerPermissions — site admins always get true
	// -------------------------------------------------------------------------

	public function testSetAdministerPermissionsReturnsTrueForSiteAdmin(): void {
		$user   = $this->createUser();
		$user->makeAdmin();
		$entity = $this->createObject(['subtype' => 'testobj', 'owner_guid' => $user->guid]);

		$hook = $this->mockHook(['entity' => $entity, 'user' => $user]);

		$handler = new SetAdministerPermissions();
		$this->assertTrue($handler($hook));
	}

	public function testSetAdministerPermissionsReturnsNullWhenEntityMissing(): void {
		$user = $this->createUser();

		$hook = $this->mockHook(['user' => $user]);

		$handler = new SetAdministerPermissions();
		$this->assertNull($handler($hook));
	}

	// -------------------------------------------------------------------------
	// SetReadPermissions — returns null without entity/user
	// -------------------------------------------------------------------------

	public function testSetReadPermissionsReturnsNullWhenEntityMissing(): void {
		$user = $this->createUser();
		$hook = $this->mockHook(['user' => $user]);

		$handler = new SetReadPermissions();
		$this->assertNull($handler($hook));
	}

	public function testSetReadPermissionsReturnsNullWhenUserMissing(): void {
		$entity = $this->createObject(['subtype' => 'testobj']);
		$hook   = $this->mockHook(['entity' => $entity]);

		$handler = new SetReadPermissions();
		$this->assertNull($handler($hook));
	}

	public function testSetReadPermissionsAllowsWhenRoleGrantsRead(): void {
		$user   = $this->createUser();
		$entity = $this->createObject(['subtype' => 'readableobj', 'owner_guid' => $user->guid]);

		$svc = elgg()->roles;
		$svc->register('reader_role');
		$svc->reader_role->onRead('object', 'readableobj', Role::ALLOW, Role::OVERRIDE);
		$svc->assign('reader_role', $user);

		$hook    = $this->mockHook(['entity' => $entity, 'user' => $user]);
		$handler = new SetReadPermissions();
		$this->assertTrue($handler($hook));

		$svc->unassign('reader_role', $user);
		$svc->unregister('reader_role');
	}

	// -------------------------------------------------------------------------
	// SetEditPermissions — returns null without entity/user
	// -------------------------------------------------------------------------

	public function testSetEditPermissionsReturnsNullWhenEntityMissing(): void {
		$user = $this->createUser();
		$hook = $this->mockHook(['user' => $user]);

		$handler = new SetEditPermissions();
		$this->assertNull($handler($hook));
	}

	public function testSetEditPermissionsAllowsWhenRoleGrantsUpdate(): void {
		$user   = $this->createUser();
		$entity = $this->createObject(['subtype' => 'editableobj', 'owner_guid' => $user->guid]);

		$svc = elgg()->roles;
		$svc->register('editor_role');
		$svc->editor_role->onUpdate('object', 'editableobj', Role::ALLOW, Role::OVERRIDE);
		$svc->assign('editor_role', $user);

		$hook    = $this->mockHook(['entity' => $entity, 'user' => $user]);
		$handler = new SetEditPermissions();
		$this->assertTrue($handler($hook));

		$svc->unassign('editor_role', $user);
		$svc->unregister('editor_role');
	}

	// -------------------------------------------------------------------------
	// SetDeletePermissions — returns null without entity/user
	// -------------------------------------------------------------------------

	public function testSetDeletePermissionsReturnsNullWhenEntityMissing(): void {
		$user = $this->createUser();
		$hook = $this->mockHook(['user' => $user]);

		$handler = new SetDeletePermissions();
		$this->assertNull($handler($hook));
	}

	public function testSetDeletePermissionsAllowsWhenRoleGrantsDelete(): void {
		$user   = $this->createUser();
		$entity = $this->createObject(['subtype' => 'deleteobj', 'owner_guid' => $user->guid]);

		$svc = elgg()->roles;
		$svc->register('deleter_role');
		$svc->deleter_role->onDelete('object', 'deleteobj', Role::ALLOW, Role::OVERRIDE);
		$svc->assign('deleter_role', $user);

		$hook    = $this->mockHook(['entity' => $entity, 'user' => $user]);
		$handler = new SetDeletePermissions();
		$this->assertTrue($handler($hook));

		$svc->unassign('deleter_role', $user);
		$svc->unregister('deleter_role');
	}

	// -------------------------------------------------------------------------
	// SetCreatePermissions — returns null without container/user
	// -------------------------------------------------------------------------

	public function testSetCreatePermissionsReturnsNullWhenContainerMissing(): void {
		$user = $this->createUser();
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();
		$hook->method('getParam')->willReturnCallback(function ($key) use ($user) {
			return $key === 'user' ? $user : null;
		});
		$hook->method('getValue')->willReturn(null);
		$hook->method('getParams')->willReturn([]);
		$hook->method('getType')->willReturn('object');

		$handler = new SetCreatePermissions();
		$this->assertNull($handler($hook));
	}

	public function testSetCreatePermissionsReturnsNullWhenUserMissing(): void {
		$container = $this->createObject(['subtype' => 'container']);
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();
		$hook->method('getParam')->willReturnCallback(function ($key) use ($container) {
			return $key === 'container' ? $container : null;
		});
		$hook->method('getValue')->willReturn(null);
		$hook->method('getParams')->willReturn([]);
		$hook->method('getType')->willReturn('object');

		$handler = new SetCreatePermissions();
		$this->assertNull($handler($hook));
	}

	// -------------------------------------------------------------------------
	// SetRouteMiddleware — prepends RouteAccessMiddleware
	// -------------------------------------------------------------------------

	public function testSetRouteMiddlewarePrependsRouteAccessMiddleware(): void {
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();
		$hook->method('getValue')->willReturn(['middleware' => ['SomeOtherMiddleware']]);

		$handler = new SetRouteMiddleware();
		$result  = $handler($hook);

		$this->assertSame(RouteAccessMiddleware::class, $result['middleware'][0]);
		$this->assertContains('SomeOtherMiddleware', $result['middleware']);
	}

	public function testSetRouteMiddlewareWorksWithNoExistingMiddleware(): void {
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();
		$hook->method('getValue')->willReturn([]);

		$handler = new SetRouteMiddleware();
		$result  = $handler($hook);

		$this->assertSame([RouteAccessMiddleware::class], $result['middleware']);
	}

	// -------------------------------------------------------------------------
	// input/role view — renders a select element with selectable roles only
	// -------------------------------------------------------------------------

	public function testRoleInputViewRendersSelectElement(): void {
		$out = elgg_view('input/role', ['name' => 'test_role']);
		$this->assertStringContainsString('<select', $out);
	}

	public function testRoleInputViewShowsSelectableRoles(): void {
		$svc = elgg()->roles;
		$svc->register('viewable_role');

		$out = elgg_view('input/role', ['name' => 'test_role']);
		$this->assertStringContainsString('value="viewable_role"', $out);

		$svc->unregister('viewable_role');
	}

	public function testRoleInputViewExcludesDefaultRoles(): void {
		$out = elgg_view('input/role', ['name' => 'test_role']);
		// Default roles are not selectable and must not appear as options
		$this->assertStringNotContainsString('value="user"', $out);
		$this->assertStringNotContainsString('value="admin"', $out);
		$this->assertStringNotContainsString('value="guest"', $out);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function mockHook(array $params = []): \Elgg\Hook {
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();
		$hook->method('getEntityParam')->willReturnCallback(function () use ($params) {
			return $params['entity'] ?? null;
		});
		$hook->method('getParam')->willReturnCallback(function ($key, $default = null) use ($params) {
			return array_key_exists($key, $params) ? $params[$key] : $default;
		});
		$hook->method('getValue')->willReturn(null);
		$hook->method('getParams')->willReturn($params);
		return $hook;
	}
}
