<?php

namespace hypeJunction\Capabilities;

use Elgg\IntegrationTestCase;

/**
 * Behavior tests for the Roles service: registration, assignment, and default role resolution.
 * Also covers Rule::grants() STACK/OVERRIDE logic and Role capability inheritance.
 */
class RolesTest extends IntegrationTestCase {

	/** @var Roles */
	private $roles;

	/** @var string[] role names to clean up after each test */
	private $registeredRoles = [];

	public function getPluginID(): string {
		return 'hypecapabilities';
	}

	public function up() {
		$this->roles = elgg()->roles;
	}

	public function down() {
		foreach ($this->registeredRoles as $name) {
			$this->roles->unregister($name);
		}
		$this->registeredRoles = [];
	}

	private function makeRole(string $name, array $extends = [], int $priority = 500): Role {
		$this->roles->register($name, $extends, $priority);
		$this->registeredRoles[] = $name;
		return $this->roles->$name;
	}

	// -----------------------------------------------------------------------
	// Rule::grants() — pure logic, tested here to avoid a separate bootstrap
	// -----------------------------------------------------------------------

	public function testRuleStackAllowWithNullCurrentReturnsTrue(): void {
		$rule = new Rule(Role::ALLOW, Role::STACK);
		$this->assertTrue($rule->grants(null));
	}

	public function testRuleStackAllowWithTrueCurrentReturnsTrue(): void {
		$rule = new Rule(Role::ALLOW, Role::STACK);
		$this->assertTrue($rule->grants(true));
	}

	public function testRuleStackAllowWithFalseCurrentReturnsFalse(): void {
		$rule = new Rule(Role::ALLOW, Role::STACK);
		$this->assertFalse($rule->grants(false));
	}

	public function testRuleStackDenyAlwaysReturnsFalse(): void {
		$rule = new Rule(Role::DENY, Role::STACK);
		$this->assertFalse($rule->grants(null));
		$this->assertFalse($rule->grants(true));
		$this->assertFalse($rule->grants(false));
	}

	public function testRuleOverrideAllowAlwaysReturnsTrue(): void {
		$rule = new Rule(Role::ALLOW, Role::OVERRIDE);
		$this->assertTrue($rule->grants(null));
		$this->assertTrue($rule->grants(false));
		$this->assertTrue($rule->grants(true));
	}

	public function testRuleOverrideDenyAlwaysReturnsFalse(): void {
		$rule = new Rule(Role::DENY, Role::OVERRIDE);
		$this->assertFalse($rule->grants(null));
		$this->assertFalse($rule->grants(true));
		$this->assertFalse($rule->grants(false));
	}

	// -----------------------------------------------------------------------
	// Role — capability registration and inheritance
	// -----------------------------------------------------------------------

	public function testRoleIsSelectableForCustomRole(): void {
		$role = $this->makeRole('test_selectable_' . uniqid());
		$this->assertTrue($role->isSelectable());
	}

	public function testRoleIsNotSelectableForBuiltinRoles(): void {
		foreach (['user', 'admin', 'guest'] as $name) {
			$this->assertFalse($this->roles->$name->isSelectable(), "$name should not be selectable");
		}
	}

	public function testRoleCapabilityRegistration(): void {
		$role = $this->makeRole('test_caps_' . uniqid());
		$role->onCreate('object', 'blog', Role::ALLOW);
		$role->onRead('object', 'blog', Role::DENY, Role::OVERRIDE);
		$role->onUpdate('object', 'blog', Role::ALLOW);
		$role->onDelete('object', 'blog', Role::DENY, Role::OVERRIDE);
		$role->onAdminister('object', 'blog', Role::ALLOW);

		$caps = $role->getCapabilities();

		$this->assertInstanceOf(Rule::class, $caps[Role::CREATE]['object']['blog']);
		$this->assertInstanceOf(Rule::class, $caps[Role::READ]['object']['blog']);
		$this->assertInstanceOf(Rule::class, $caps[Role::UPDATE]['object']['blog']);
		$this->assertInstanceOf(Rule::class, $caps[Role::DELETE]['object']['blog']);
		$this->assertInstanceOf(Rule::class, $caps[Role::ADMINISTER]['object']['blog']);
	}

	public function testRoleInheritsCapabilitiesFromParent(): void {
		$parent = $this->makeRole('test_parent_' . uniqid());
		$parent->onRead('object', 'blog', Role::ALLOW, Role::OVERRIDE);

		$child = $this->makeRole('test_child_' . uniqid(), [$parent->getRole()]);

		// Child inherits READ rule from parent
		$caps = $child->getCapabilities();
		$this->assertArrayHasKey('object', $caps[Role::READ] ?? []);
		$this->assertArrayHasKey('blog', $caps[Role::READ]['object'] ?? []);
	}

	public function testChildRoleOverridesParentCapability(): void {
		$parent = $this->makeRole('test_ov_parent_' . uniqid());
		$parent->onRead('object', 'blog', Role::DENY, Role::OVERRIDE);

		$child = $this->makeRole('test_ov_child_' . uniqid(), [$parent->getRole()]);
		$child->onRead('object', 'blog', Role::ALLOW, Role::OVERRIDE);

		$caps = $child->getCapabilities();
		// Child's own definition wins over inherited one
		$rule = $caps[Role::READ]['object']['blog'];
		$this->assertTrue($rule->grants(null));
	}

	// -----------------------------------------------------------------------
	// Roles service — register / unregister
	// -----------------------------------------------------------------------

	public function testDefaultRolesExist(): void {
		foreach (['user', 'admin', 'guest'] as $name) {
			$this->assertInstanceOf(Role::class, $this->roles->getRoleByName($name), "$name role missing");
		}
	}

	public function testRegisterNewRole(): void {
		$name = 'test_reg_' . uniqid();
		$this->roles->register($name);
		$this->registeredRoles[] = $name;
		$this->assertInstanceOf(Role::class, $this->roles->getRoleByName($name));
	}

	public function testRegisterDefaultRoleIsNoOp(): void {
		// Registering 'user' must not replace the existing Role object
		$before = $this->roles->user;
		$this->roles->register('user', ['admin']);
		$this->assertSame($before, $this->roles->user);
	}

	public function testUnregisterCustomRole(): void {
		$name = 'test_unreg_' . uniqid();
		$this->roles->register($name);
		$this->roles->unregister($name);
		$this->assertNull($this->roles->getRoleByName($name));
	}

	public function testUnregisterDefaultRoleIsNoOp(): void {
		$this->roles->unregister('admin');
		$this->assertInstanceOf(Role::class, $this->roles->getRoleByName('admin'));
	}

	public function testAllReturnsOnlySelectableRoles(): void {
		$name = 'test_all_' . uniqid();
		$this->roles->register($name);
		$this->registeredRoles[] = $name;

		$all = $this->roles->all(true);
		foreach ($all as $role) {
			$this->assertTrue($role->isSelectable(), $role->getRole() . ' should be selectable');
		}
		$this->assertArrayHasKey($name, $all);
	}

	// -----------------------------------------------------------------------
	// Roles service — assign / unassign / hasRole
	// -----------------------------------------------------------------------

	public function testAssignAndHasRole(): void {
		$roleName = 'test_assign_' . uniqid();
		$this->roles->register($roleName);
		$this->registeredRoles[] = $roleName;

		$user = $this->createUser();
		$this->roles->assign($roleName, $user);

		$found = $this->roles->hasRole($roleName, $user);
		$this->assertInstanceOf(Role::class, $found);
		$this->assertEquals($roleName, $found->getRole());
	}

	public function testUnassignRemovesRole(): void {
		$roleName = 'test_unassign_' . uniqid();
		$this->roles->register($roleName);
		$this->registeredRoles[] = $roleName;

		$user = $this->createUser();
		$this->roles->assign($roleName, $user);
		$this->roles->unassign($roleName, $user);

		$this->assertNull($this->roles->hasRole($roleName, $user));
	}

	public function testHasRoleReturnsFalseForUnassignedRole(): void {
		$roleName = 'test_no_role_' . uniqid();
		$this->roles->register($roleName);
		$this->registeredRoles[] = $roleName;

		$user = $this->createUser();
		$this->assertNull($this->roles->hasRole($roleName, $user));
	}

	public function testAssignSelectableRoleOnly(): void {
		// Non-selectable roles cannot be assigned
		$user = $this->createUser();
		$this->roles->assign('user', $user); // no-op for built-in
		// No exception, but built-in roles are never stored as relationships
		// getRoles() returns 'user' from the default branch, not from a DB relationship
		$roles = $this->roles->getRoles($user);
		$names = array_map(fn($r) => $r->getRole(), $roles);
		$this->assertContains('user', $names);
	}

	// -----------------------------------------------------------------------
	// Roles service — getRoles default resolution
	// -----------------------------------------------------------------------

	public function testGetRolesReturnsGuestForNoUser(): void {
		$roles = $this->roles->getRoles(null);
		$names = array_map(fn($r) => $r->getRole(), $roles);
		$this->assertContains('guest', $names);
	}

	public function testGetRolesReturnsUserRoleForRegularUser(): void {
		$user = $this->createUser();
		$roles = $this->roles->getRoles($user);
		$names = array_map(fn($r) => $r->getRole(), $roles);
		$this->assertContains('user', $names);
	}

	public function testGetRolesReturnsAdminRoleForAdminUser(): void {
		$admin = $this->createUser();
		$admin->makeAdmin();

		$roles = $this->roles->getRoles($admin);
		$names = array_map(fn($r) => $r->getRole(), $roles);
		$this->assertContains('admin', $names);
	}

	public function testGetRolesReturnsCustomRoleWhenAssigned(): void {
		$roleName = 'test_getrol_' . uniqid();
		$this->roles->register($roleName);
		$this->registeredRoles[] = $roleName;

		$user = $this->createUser();
		$this->roles->assign($roleName, $user);

		$roles = $this->roles->getRoles($user);
		$names = array_map(fn($r) => $r->getRole(), $roles);
		$this->assertContains($roleName, $names);
	}

	// -----------------------------------------------------------------------
	// Roles::can() static — custom capability via capability hook
	// -----------------------------------------------------------------------

	public function testCanDefaultsToTrueWithNoRuleConfigured(): void {
		$user = $this->createUser();
		elgg_get_session()->setLoggedInUser($user);
		try {
			$result = Roles::can('view', 'widget_' . uniqid(), null, null, true);
			$this->assertTrue($result);
		} finally {
			elgg_get_session()->removeLoggedInUser();
		}
	}

	public function testCanReturnsFalseWhenRoleDeniesCapability(): void {
		$roleName = 'test_can_deny_' . uniqid();
		$component = 'dash_' . uniqid();

		$this->roles->register($roleName);
		$this->registeredRoles[] = $roleName;
		$this->roles->$roleName->on('view', $component, Role::DENY, Role::OVERRIDE);

		$user = $this->createUser();
		$this->roles->assign($roleName, $user);
		elgg_get_session()->setLoggedInUser($user);

		try {
			$result = Roles::can('view', $component, null, null, true);
			$this->assertFalse($result);
		} finally {
			elgg_get_session()->removeLoggedInUser();
		}
	}

	public function testCanReturnsTrueWhenRoleAllowsCapability(): void {
		$roleName = 'test_can_allow_' . uniqid();
		$component = 'dash_' . uniqid();

		$this->roles->register($roleName);
		$this->registeredRoles[] = $roleName;
		$this->roles->$roleName->on('view', $component, Role::ALLOW, Role::OVERRIDE);

		$user = $this->createUser();
		$this->roles->assign($roleName, $user);
		elgg_get_session()->setLoggedInUser($user);

		try {
			$result = Roles::can('view', $component, null, null, false);
			$this->assertTrue($result);
		} finally {
			elgg_get_session()->removeLoggedInUser();
		}
	}
}
