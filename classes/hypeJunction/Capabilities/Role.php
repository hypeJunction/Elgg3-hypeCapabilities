<?php

namespace hypeJunction\Capabilities;

use ElggEntity;
use ElggUser;

final class Role implements RoleInterface {

	const CREATE = 'create';
	const READ = 'read';
	const UPDATE = 'update';
	const DELETE = 'delete';
	const ADMINISTER = 'administer';
	const ACTION = 'action';
	const ROUTE = 'route';

	const ALLOW = true;
	const DENY = false;

	const OVERRIDE = 'override';
	const STACK = 'stack';

	/**
	 * @var string
	 */
	protected $role;

	/**
	 * @var string[]
	 */
	protected $extends = [];

	/**
	 * @var array
	 */
	protected $capabilities = [];

	/**
	 * @var int
	 */
	protected $priority;

	/**
	 * Constructor
	 *
	 * @param string   $role     Role name
	 * @param string[] $extends  Extension role names
	 * @param int      $priority Role priority
	 *                           Roles with higher priority will take precedence during grant resolution
	 */
	public function __construct($role, array $extends = [], $priority = 500) {
		$this->role = $role;
		$this->extends = $extends;
		$this->priority = $priority;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRole() {
		return $this->role;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLabel() {
		return elgg_echo("roles:role:$this->role");
	}

	/**
	 * {@inheritdoc}
	 */
	public function isSelectable() {
		return !in_array($this->role, ['user', 'admin', 'guest']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setPriority($priority = 500) {
		$this->priority = $priority;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getExtends() {
		return $this->extends;
	}

	/**
	 * {@inheritdoc}
	 */
	public function addExtend($role) {
		$this->extends[] = $role;
	}

	/**
	 * {@inheritdoc}
	 */
	public function onCreate($type, $subtype, $rule, $condition = self::STACK) {
		$this->capabilities[self::CREATE][$type][$subtype] = new Rule($rule, $condition);
	}

	/**
	 * {@inheritdoc}
	 */
	public function onRead($type, $subtype, $rule, $condition = self::STACK) {
		$this->capabilities[self::READ][$type][$subtype] = new Rule($rule, $condition);
	}

	/**
	 * {@inheritdoc}
	 */
	public function onUpdate($type, $subtype, $rule, $condition = self::STACK) {
		$this->capabilities[self::UPDATE][$type][$subtype] = new Rule($rule, $condition);
	}

	/**
	 * {@inheritdoc}
	 */
	public function onDelete($type, $subtype, $rule, $condition = self::STACK) {
		$this->capabilities[self::DELETE][$type][$subtype] = new Rule($rule, $condition);
	}

	/**
	 * {@inheritdoc}
	 */
	public function onAdminister($type, $subtype, $rule, $condition = self::STACK) {
		$this->capabilities[self::ADMINISTER][$type][$subtype] = new Rule($rule, $condition);
	}

	/**
	 * {@inheritdoc}
	 */
	public function onAction($action, $rule, $condition = self::STACK) {
		$this->capabilities[self::ROUTE]['route']["action:$action"] = new Rule($rule, $condition);
	}

	/**
	 * {@inheritdoc}
	 */
	public function onRouteAccess($route, $rule, $condition = self::STACK) {
		$this->capabilities[self::ROUTE]['route'][$route] = new Rule($rule, $condition);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCapabilities() {

		$merge = function (array $from, array $to = []) {
			foreach ($from as $capability => $capability_options) {
				foreach ($capability_options as $type => $subtype_options) {
					foreach ($subtype_options as $subtype => $rule) {
						$to[$capability][$type][$subtype] = $rule;
					}
				}
			}

			return $to;
		};

		$capabilities = [];

		foreach ($this->getExtends() as $extend) {
			$role = elgg()->roles->$extend;
			/* @var $role \hypeJunction\Capabilities\Role */

			$parent_capabilities = $role->getCapabilities();

			$capabilities = $merge($parent_capabilities, $capabilities);
		}

		$capabilities = $merge($this->capabilities, $capabilities);

		return $capabilities;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEntityRule($action, ElggEntity $target = null, ElggUser $actor = null, array $params = []) {

		if (!$target) {
			return null;
		}

		if (!isset($actor) && elgg_is_logged_in()) {
			$actor = elgg_get_logged_in_user_entity();
		}

		$capabilities = $this->getCapabilities();

		if ($action == Role::CREATE) {
			$type = elgg_extract('type', $params);
			$subtype = elgg_extract('subtype', $params);
		} else {
			$type = $target->type;
			$subtype = $target->subtype;
		}

		if (isset($capabilities[$action][$type][$subtype])) {
			$rule = $capabilities[$action][$type][$subtype];
			/* @var $rule RuleInterface */

			$rule->setContext(new Context($target, $actor, $params));

			return $rule;
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRouteRule($route, ElggEntity $target = null, ElggUser $user = null, array $params = []) {

		$capabilities = $this->getCapabilities();

		if (isset($capabilities[self::ROUTE]['route'][$route])) {
			$rule = $capabilities[self::ROUTE]['route'][$route];
			/* @var $rule RuleInterface */

			$rule->setContext(new Context($target, $user, $params));

			return $rule;
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function on($action, $component, $rule, $condition = self::STACK) {
		$this->capabilities[$action]['custom'][$component] = new Rule($rule, $condition);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCustomRule($action, $component, ElggEntity $target = null, ElggUser $user = null, array $params = []) {

		$capabilities = $this->getCapabilities();

		if (isset($capabilities[$action]['custom'][$component])) {
			$rule = $capabilities[$action]['custom'][$component];
			/* @var $rule RuleInterface */

			$rule->setContext(new Context($target, $user, $params));

			return $rule;
		}

		return null;
	}
}