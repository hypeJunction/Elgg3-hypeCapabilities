<?php

namespace hypeJunction\Capabilities;

use Elgg\IntegrationTestCase;
use ElggObject;

/**
 * Behavior tests for the seven permission hook handlers registered in Bootstrap::init().
 *
 * Each handler is called directly via __invoke() with a mocked \Elgg\Hook so we can
 * assert exactly what the handler returns without triggering unrelated handlers from
 * other active plugins.
 */
class PermissionsTest extends IntegrationTestCase {

	/** @var Roles */
	private $roles;

	/** @var string[] role names registered in this test, cleaned up in down() */
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
		if (elgg_is_logged_in()) {
			elgg_get_session()->removeLoggedInUser();
		}
	}

	private function makeRole(string $name, array $extends = []): Role {
		$this->roles->register($name, $extends);
		$this->registeredRoles[] = $name;
		return $this->roles->$name;
	}

	/**
	 * Build a minimal \Elgg\Hook mock.
	 * Only stubs what the permission handlers actually read.
	 */
	private function makeHook(
		?\ElggEntity $entity,
		?\ElggUser $user,
		$currentValue = null,
		string $hookType = 'object'
	): \Elgg\Hook {
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();

		$hook->method('getEntityParam')->willReturn($entity);
		$hook->method('getParam')->willReturnCallback(function ($name) use ($entity, $user) {
			if ($name === 'user') {
				return $user;
			}
			if ($name === 'entity') {
				return $entity;
			}
			if ($name === 'container' && $entity) {
				return $entity->getContainerEntity();
			}
			return null;
		});
		$hook->method('getParams')->willReturnCallback(function () use ($entity, $user) {
			return ['entity' => $entity, 'user' => $user];
		});
		$hook->method('getValue')->willReturn($currentValue);
		$hook->method('getType')->willReturn($hookType);
		$hook->method('getName')->willReturn('permissions_check');

		return $hook;
	}

	// -----------------------------------------------------------------------
	// SetAdministerPermissions
	// -----------------------------------------------------------------------

	public function testAdministerNullEntityReturnsNull(): void {
		$user = $this->createUser();
		$hook = $this->makeHook(null, $user);
		$result = (new SetAdministerPermissions())($hook);
		$this->assertNull($result);
	}

	public function testAdministerNullUserReturnsNull(): void {
		$owner = $this->createUser();
		$entity = $this->createObject(['subtype' => 'test_adm_' . uniqid(), 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, null);
		$result = (new SetAdministerPermissions())($hook);
		$this->assertNull($result);
	}

	public function testAdministerAdminUserAlwaysGranted(): void {
		$owner = $this->createUser();
		$admin = $this->createUser();
		$admin->makeAdmin();

		$entity = $this->createObject(['subtype' => 'test_adm_a_' . uniqid(), 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $admin, false);

		$result = (new SetAdministerPermissions())($hook);
		$this->assertTrue($result);
	}

	public function testAdministerNonAdminWithNoRuleReturnsNull(): void {
		$owner = $this->createUser();
		$other = $this->createUser();
		$entity = $this->createObject(['subtype' => 'test_adm_n_' . uniqid(), 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $other, false);

		$result = (new SetAdministerPermissions())($hook);
		$this->assertNull($result);
	}

	public function testAdministerGrantedByRoleRule(): void {
		$roleName = 'test_adm_role_' . uniqid();
		$subtype = 'post_' . uniqid();

		$role = $this->makeRole($roleName);
		$role->onAdminister('object', $subtype, Role::ALLOW, Role::OVERRIDE);

		$owner = $this->createUser();
		$actor = $this->createUser();
		$this->roles->assign($roleName, $actor);

		$entity = $this->createObject(['subtype' => $subtype, 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $actor, false);

		$result = (new SetAdministerPermissions())($actor->isAdmin() ? $hook : $hook);
		// actor is not admin so role-based rule is checked
		$this->assertTrue($result);
	}

	// -----------------------------------------------------------------------
	// SetReadPermissions (gatekeeper hook)
	// -----------------------------------------------------------------------

	public function testReadNullEntityReturnsNull(): void {
		$user = $this->createUser();
		$hook = $this->makeHook(null, $user);
		$result = (new SetReadPermissions())($hook);
		$this->assertNull($result);
	}

	public function testReadNullUserReturnsNull(): void {
		$owner = $this->createUser();
		$entity = $this->createObject(['subtype' => 'test_rd_' . uniqid(), 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, null);
		$result = (new SetReadPermissions())($hook);
		$this->assertNull($result);
	}

	public function testReadNoMatchingRuleReturnsNull(): void {
		$owner = $this->createUser();
		$reader = $this->createUser();
		$entity = $this->createObject(['subtype' => 'test_rd_nm_' . uniqid(), 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $reader, false);

		$result = (new SetReadPermissions())($hook);
		$this->assertNull($result);
	}

	public function testReadGrantedByAllowOverrideRule(): void {
		$roleName = 'test_read_allow_' . uniqid();
		$subtype = 'art_' . uniqid();

		$role = $this->makeRole($roleName);
		$role->onRead('object', $subtype, Role::ALLOW, Role::OVERRIDE);

		$owner = $this->createUser();
		$reader = $this->createUser();
		$this->roles->assign($roleName, $reader);

		$entity = $this->createObject(['subtype' => $subtype, 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $reader, false);

		$result = (new SetReadPermissions())($hook);
		$this->assertTrue($result, 'ALLOW OVERRIDE rule should grant read even when current=false');
	}

	public function testReadDeniedByDenyOverrideRule(): void {
		$roleName = 'test_read_deny_' . uniqid();
		$subtype = 'art_d_' . uniqid();

		$role = $this->makeRole($roleName);
		$role->onRead('object', $subtype, Role::DENY, Role::OVERRIDE);

		$owner = $this->createUser();
		$reader = $this->createUser();
		$this->roles->assign($roleName, $reader);

		$entity = $this->createObject(['subtype' => $subtype, 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $reader, true);

		$result = (new SetReadPermissions())($hook);
		$this->assertFalse($result, 'DENY OVERRIDE rule should deny read even when current=true');
	}

	// -----------------------------------------------------------------------
	// SetEditPermissions (permissions_check hook)
	// -----------------------------------------------------------------------

	public function testEditNullEntityReturnsNull(): void {
		$user = $this->createUser();
		$hook = $this->makeHook(null, $user);
		$result = (new SetEditPermissions())($hook);
		$this->assertNull($result);
	}

	public function testEditGrantedByUpdateRule(): void {
		$roleName = 'test_edit_u_' . uniqid();
		$subtype = 'note_' . uniqid();

		$role = $this->makeRole($roleName);
		$role->onUpdate('object', $subtype, Role::ALLOW, Role::OVERRIDE);

		$owner = $this->createUser();
		$editor = $this->createUser();
		$this->roles->assign($roleName, $editor);

		$entity = $this->createObject(['subtype' => $subtype, 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $editor, false);

		$result = (new SetEditPermissions())($hook);
		$this->assertTrue($result);
	}

	public function testEditGrantedByAdministerRule(): void {
		// SetEditPermissions falls through to ADMINISTER if no UPDATE rule matches
		$roleName = 'test_edit_a_' . uniqid();
		$subtype = 'note_a_' . uniqid();

		$role = $this->makeRole($roleName);
		// Only administer, no update
		$role->onAdminister('object', $subtype, Role::ALLOW, Role::OVERRIDE);

		$owner = $this->createUser();
		$editor = $this->createUser();
		$this->roles->assign($roleName, $editor);

		$entity = $this->createObject(['subtype' => $subtype, 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $editor, false);

		$result = (new SetEditPermissions())($hook);
		$this->assertTrue($result);
	}

	// -----------------------------------------------------------------------
	// SetDeletePermissions (permissions_check:delete hook)
	// -----------------------------------------------------------------------

	public function testDeleteNullEntityReturnsNull(): void {
		$user = $this->createUser();
		$hook = $this->makeHook(null, $user);
		$result = (new SetDeletePermissions())($hook);
		$this->assertNull($result);
	}

	public function testDeleteGrantedByDeleteRule(): void {
		$roleName = 'test_del_' . uniqid();
		$subtype = 'item_' . uniqid();

		$role = $this->makeRole($roleName);
		$role->onDelete('object', $subtype, Role::ALLOW, Role::OVERRIDE);

		$owner = $this->createUser();
		$actor = $this->createUser();
		$this->roles->assign($roleName, $actor);

		$entity = $this->createObject(['subtype' => $subtype, 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $actor, false);

		$result = (new SetDeletePermissions())($hook);
		$this->assertTrue($result);
	}

	public function testDeleteDeniedByDenyOverrideRule(): void {
		$roleName = 'test_del_d_' . uniqid();
		$subtype = 'item_d_' . uniqid();

		$role = $this->makeRole($roleName);
		$role->onDelete('object', $subtype, Role::DENY, Role::OVERRIDE);

		$owner = $this->createUser();
		$actor = $this->createUser();
		$this->roles->assign($roleName, $actor);

		$entity = $this->createObject(['subtype' => $subtype, 'owner_guid' => $owner->guid]);
		$hook = $this->makeHook($entity, $actor, true);

		$result = (new SetDeletePermissions())($hook);
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// SetCreatePermissions (container_permissions_check hook)
	// -----------------------------------------------------------------------

	public function testCreateNullContainerReturnsNull(): void {
		$user = $this->createUser();
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();
		$hook->method('getParam')->willReturnCallback(function ($name) use ($user) {
			if ($name === 'user') return $user;
			if ($name === 'container') return null;
			return null;
		});
		$hook->method('getType')->willReturn('object');
		$hook->method('getValue')->willReturn(false);
		$hook->method('getParams')->willReturn(['user' => $user]);

		$result = (new SetCreatePermissions())($hook);
		$this->assertNull($result);
	}

	public function testCreateGrantedByRoleRule(): void {
		$roleName = 'test_create_' . uniqid();
		$subtype = 'obj_c_' . uniqid();

		$role = $this->makeRole($roleName);
		$role->onCreate('object', $subtype, Role::ALLOW, Role::OVERRIDE);

		$owner = $this->createUser();
		$creator = $this->createUser();
		$this->roles->assign($roleName, $creator);

		// container_permissions_check passes a container (could be site, group, user)
		$container = elgg_get_site_entity();
		$hook = $this->getMockBuilder(\Elgg\Hook::class)->getMock();
		$hook->method('getParam')->willReturnCallback(function ($name) use ($creator, $container) {
			if ($name === 'user') return $creator;
			if ($name === 'container') return $container;
			return null;
		});
		$hook->method('getType')->willReturn('object');
		$hook->method('getValue')->willReturn(false);
		$hook->method('getParams')->willReturn(['user' => $creator, 'container' => $container, 'subtype' => $subtype]);

		$result = (new SetCreatePermissions())($hook);
		$this->assertTrue($result);
	}

	// -----------------------------------------------------------------------
	// SetCustomPermissions — via Roles::can() static proxy
	// -----------------------------------------------------------------------

	public function testCustomPermissionDefaultTrueWhenNoRule(): void {
		$user = $this->createUser();
		elgg_get_session()->setLoggedInUser($user);

		$component = 'widget_' . uniqid();
		$result = Roles::can('view', $component, null, null, true);
		$this->assertTrue($result);
	}

	public function testCustomPermissionDeniedByRoleRule(): void {
		$roleName = 'test_custom_deny_' . uniqid();
		$component = 'sidebar_' . uniqid();

		$role = $this->makeRole($roleName);
		$role->on('view', $component, Role::DENY, Role::OVERRIDE);

		$user = $this->createUser();
		$this->roles->assign($roleName, $user);
		elgg_get_session()->setLoggedInUser($user);

		$result = Roles::can('view', $component, null, null, true);
		$this->assertFalse($result);
	}

	public function testCustomPermissionAllowedByRoleRule(): void {
		$roleName = 'test_custom_allow_' . uniqid();
		$component = 'sidebar_a_' . uniqid();

		$role = $this->makeRole($roleName);
		$role->on('view', $component, Role::ALLOW, Role::OVERRIDE);

		$user = $this->createUser();
		$this->roles->assign($roleName, $user);
		elgg_get_session()->setLoggedInUser($user);

		$result = Roles::can('view', $component, null, null, false);
		$this->assertTrue($result);
	}
}
