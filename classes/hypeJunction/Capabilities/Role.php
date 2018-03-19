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
	 * Constructor
	 *
	 * @param string   $role    Role name
	 * @param string[] $extends Extension role names
	 */
	public function __construct($role, array $extends = []) {
		$this->role = $role;
		$this->extends = $extends;
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
	public function onCreate($type, $subtype, $rule) {
		$this->capabilities[self::CREATE][$type][$subtype] = $rule;
	}

	/**
	 * {@inheritdoc}
	 */
	public function onRead($type, $subtype, $rule) {
		$this->capabilities[self::READ][$type][$subtype] = $rule;
	}

	/**
	 * {@inheritdoc}
	 */
	public function onUpdate($type, $subtype, $rule) {
		$this->capabilities[self::UPDATE][$type][$subtype] = $rule;
	}

	/**
	 * {@inheritdoc}
	 */
	public function onDelete($type, $subtype, $rule) {
		$this->capabilities[self::DELETE][$type][$subtype] = $rule;
	}

	/**
	 * {@inheritdoc}
	 */
	public function onAdminister($type, $subtype, $rule) {
		$this->capabilities[self::ADMINISTER][$type][$subtype] = $rule;
	}

	/**
	 * {@inheritdoc}
	 */
	public function onAction($action, $rule) {
		$this->capabilities[self::ROUTE]['route']["action:$action"] = $rule;
	}

	/**
	 * {@inheritdoc}
	 */
	public function onRouteAccess($route, $rule) {
		$this->capabilities[self::ROUTE]['route'][$route] = $rule;
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
	 * Resolve entity capability
	 *
	 * @param string     $capability 'create', 'read', 'update', 'delete'
	 * @param ElggEntity $target     Entity
	 * @param ElggUser   $actor      User
	 * @param array      $params     Context parameters
	 *
	 * @return bool|null
	 */
	public function getEntityRule($capability, ElggEntity $target, ElggUser $actor, array $params = []) {
		$capabilities = $this->getCapabilities();
		if (isset($capabilities[$capability][$target->type][$target->subtype])) {
			$rule = $capabilities[$capability][$target->type][$target->subtype];
			if (is_bool($rule)) {
				return $rule;
			} else if (is_callable($rule)) {
				$context = new Context($target, $actor, $params);

				return call_user_func($rule, $context);
			}
		}

		return null;
	}

	/**
	 * Resolve entity capability
	 *
	 * @param string     $route  Route name
	 * @param ElggEntity $target Target entity (page owner)
	 * @param ElggUser   $user   Actor
	 * @param array      $params Context parameters
	 *
	 * @return bool|null
	 */
	public function getRouteRule($route, ElggEntity $target = null, ElggUser $user = null, array $params = []) {

		$capabilities = $this->getCapabilities();

		if (isset($capabilities[self::ROUTE]['route'][$route])) {
			$rule = $capabilities[self::ROUTE]['route'][$route];
			if (is_bool($rule)) {
				return $rule;
			} else if (is_callable($rule)) {
				$context = new Context($target, $user, $params);

				return call_user_func($rule, $context);
			}
		}

		return null;
	}
}