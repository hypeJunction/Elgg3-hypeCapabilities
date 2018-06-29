<?php

namespace hypeJunction\Capabilities;

use ElggEntity;
use ElggUser;

interface RoleInterface {

	/**
	 * Get role name
	 * @return string
	 */
	public function getRole();

	/**
	 * Get role label
	 * @return string
	 */
	public function getLabel();

	/**
	 * Can this role be asigned to a user
	 * Used to avoid accidental assignment of default roles (guest, admin, user)
	 *
	 * @return bool
	 */
	public function isSelectable();

	/**
	 * Get role priority
	 * @return int
	 */
	public function getPriority();

	/**
	 * Set priority
	 *
	 * @param int $priority Priority
	 *
	 * @return void
	 */
	public function setPriority($priority = 500);

	/**
	 * Returns roles that this role extends
	 *
	 * @return string[]
	 */
	public function getExtends();

	/**
	 * Add a role name that this role extends
	 *
	 * @param string $role Role name
	 *
	 * @return void
	 */
	public function addExtend($role);

	/**
	 * Add a rule that applies to entity create operations
	 *
	 * @param string          $type      Entity type
	 * @param string          $subtype   Entity subtype
	 * @param callable|bool   $rule      Rule (true = allow, false = deny)
	 * @param string|callable $condition Condition
	 *
	 * @return mixed
	 */
	public function onCreate($type, $subtype, $rule, $condition = null);

	/**
	 * Add a rule that applies to entity read/view operations
	 *
	 * @param string          $type      Entity type
	 * @param string          $subtype   Entity subtype
	 * @param callable|bool   $rule      Rule (true = allow, false = deny)
	 * @param string|callable $condition Condition
	 *
	 * @return mixed
	 */
	public function onRead($type, $subtype, $rule, $condition = null);

	/**
	 * Add a rule that applies to entity update operations
	 *
	 * @param string          $type      Entity type
	 * @param string          $subtype   Entity subtype
	 * @param callable|bool   $rule      Rule (true = allow, false = deny)
	 * @param string|callable $condition Condition
	 *
	 * @return mixed
	 */
	public function onUpdate($type, $subtype, $rule, $condition = null);

	/**
	 * Add a rule that applies to entity delete operations
	 *
	 * @param string          $type      Entity type
	 * @param string          $subtype   Entity subtype
	 * @param callable|bool   $rule      Rule (true = allow, false = deny)
	 * @param string|callable $condition Condition
	 *
	 * @return mixed
	 */
	public function onDelete($type, $subtype, $rule, $condition = null);

	/**
	 * Add a rule that applies to entity administration operations, e.g. published status changes, approval etc
	 *
	 * @param string          $type      Entity type
	 * @param string          $subtype   Entity subtype
	 * @param callable|bool   $rule      Rule (true = allow, false = deny)
	 * @param string|callable $condition Condition
	 *
	 * @return mixed
	 */
	public function onAdminister($type, $subtype, $rule, $condition = null);

	/**
	 * Add a rule that applies to action access
	 *
	 * @param string          $action    Action name
	 * @param callable|bool   $rule      Rule (true = allow, false = deny)
	 * @param string|callable $condition Condition
	 *
	 * @return mixed
	 */
	public function onAction($action, $rule, $condition = null);

	/**
	 * Add a rule that applies to route access
	 *
	 * @param string          $route     Route name
	 * @param callable|bool   $rule      Rule (true = allow, false = deny)
	 * @param string|callable $condition Condition
	 *
	 * @return mixed
	 */
	public function onRouteAccess($route, $rule, $condition = null);

	/**
	 * Get role capabilities
	 * @return array
	 */
	public function getCapabilities();

	/**
	 * Resolve entity capability
	 *
	 * @param string     $action 'create', 'read', 'update', 'delete'
	 * @param ElggEntity $target Entity
	 * @param ElggUser   $actor  User
	 * @param array      $params Context parameters
	 *
	 * @return RuleInterface|null
	 */
	public function getEntityRule($action, ElggEntity $target = null, ElggUser $actor = null, array $params = []);

	/**
	 * Resolve entity capability
	 *
	 * @param string     $route  Route name
	 * @param ElggEntity $target Target entity (page owner)
	 * @param ElggUser   $user   Actor
	 * @param array      $params Context parameters
	 *
	 * @return RuleInterface|null
	 */
	public function getRouteRule($route, ElggEntity $target = null, ElggUser $user = null, array $params = []);

	/**
	 * Register custom capability rule
	 *
	 * @param string          $action    Action/capability name
	 * @param string          $component Component/module name
	 * @param callable|bool   $rule      Rule (true = allow, false = deny)
	 * @param string|callable $condition Condition
	 *
	 * @return mixed
	 */
	public function on($action, $component, $rule, $condition = null);

	/**
	 * Resolve custom capability
	 *
	 * @param string     $action    'create', 'read', 'update', 'delete'
	 * @param string     $component Component name
	 * @param ElggEntity $target    Entity
	 * @param ElggUser   $actor     User
	 * @param array      $params    Context parameters
	 *
	 * @return RuleInterface|null
	 */
	public function getCustomRule($action, $component, ElggEntity $target = null, ElggUser $actor = null, array $params = []);
}