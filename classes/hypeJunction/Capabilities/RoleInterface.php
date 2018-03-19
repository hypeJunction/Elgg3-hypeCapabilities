<?php

namespace hypeJunction\Capabilities;

interface RoleInterface {

	/**
	 * Get role name
	 * @return string
	 */
	public function getRole();

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
	 * @param string        $type    Entity type
	 * @param string        $subtype Entity subtype
	 * @param callable|bool $rule    Rule (true = allow, false = deny)
	 *                               Optionally, a rule can be defined as callable,
	 *                               which receives an instance of ContextInterface and must return true or false
	 *
	 * @return mixed
	 */
	public function onCreate($type, $subtype, $rule);

	/**
	 * Add a rule that applies to entity read/view operations
	 *
	 * @param string        $type    Entity type
	 * @param string        $subtype Entity subtype
	 * @param callable|bool $rule    Rule (true = allow, false = deny)
	 *                               Optionally, a rule can be defined as callable,
	 *                               which receives an instance of ContextInterface and must return true or false
	 *
	 * @return mixed
	 */
	public function onRead($type, $subtype, $rule);

	/**
	 * Add a rule that applies to entity update operations
	 *
	 * @param string        $type    Entity type
	 * @param string        $subtype Entity subtype
	 * @param callable|bool $rule    Rule (true = allow, false = deny)
	 *                               Optionally, a rule can be defined as callable,
	 *                               which receives an instance of ContextInterface and must return true or false
	 *
	 * @return mixed
	 */
	public function onUpdate($type, $subtype, $rule);

	/**
	 * Add a rule that applies to entity delete operations
	 *
	 * @param string        $type    Entity type
	 * @param string        $subtype Entity subtype
	 * @param callable|bool $rule    Rule (true = allow, false = deny)
	 *                               Optionally, a rule can be defined as callable,
	 *                               which receives an instance of ContextInterface and must return true or false
	 *
	 * @return mixed
	 */
	public function onDelete($type, $subtype, $rule);

	/**
	 * Add a rule that applies to entity administration operations, e.g. published status changes, approval etc
	 *
	 * @param string        $type    Entity type
	 * @param string        $subtype Entity subtype
	 * @param callable|bool $rule    Rule (true = allow, false = deny)
	 *                               Optionally, a rule can be defined as callable,
	 *                               which receives an instance of ContextInterface and must return true or false
	 *
	 * @return mixed
	 */
	public function onAdminister($type, $subtype, $rule);

	/**
	 * Add a rule that applies to action access
	 *
	 * @param string        $action  Action name
	 * @param callable|bool $rule    Rule (true = allow, false = deny)
	 *                               Optionally, a rule can be defined as callable,
	 *                               which receives an instance of ContextInterface and must return true or false
	 *
	 * @return mixed
	 */
	public function onAction($action, $rule);

	/**
	 * Add a rule that applies to route access
	 *
	 * @param string        $route   Route name
	 * @param callable|bool $rule    Rule (true = allow, false = deny)
	 *                               Optionally, a rule can be defined as callable,
	 *                               which receives an instance of ContextInterface and must return true or false
	 *
	 * @return mixed
	 */
	public function onRouteAccess($route, $rule);

	/**
	 * Get role capabilities
	 * @return array
	 */
	public function getCapabilities();

}