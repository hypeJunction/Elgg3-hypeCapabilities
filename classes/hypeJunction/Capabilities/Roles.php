<?php

namespace hypeJunction\Capabilities;

use DatabaseException;
use Elgg\Database\Select;
use Elgg\Di\ServiceFacade;
use ElggEntity;
use ElggUser;

/**
 * @property-read Role $user
 * @property-read Role $admin
 * @property-read Role $guest
 */
class Roles {

	use ServiceFacade;

	/**
	 * @var array
	 */
	protected $defaults = ['user', 'admin', 'guest'];

	/**
	 * @var Role[]
	 */
	protected $roles = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		foreach ($this->defaults as $role) {
			$this->roles[$role] = new Role($role);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public static function name() {
		return 'roles';
	}

	/**
	 * Get a role
	 *
	 * @param string $role Role name
	 *
	 * @return Role
	 */
	public function __get($role) {
		if (!isset($this->roles[$role])) {
			$this->register($role);
		}

		return $this->roles[$role];
	}

	/**
	 * Get role by its name
	 *
	 * @param string $name Role name
	 *
	 * @return Role|null
	 */
	public function getRoleByName($name) {
		return elgg_extract($name, $this->roles);
	}

	/**
	 * Get all roles
	 *
	 * @param bool $selectable Only show selectable roles
	 *
	 * @return Role[]
	 */
	public function all($selectable = true) {
		$roles = $this->roles;
		if (!$selectable) {
			return $roles;
		}

		return array_filter($roles, function (Role $e) {
			return $e->isSelectable();
		});
	}

	/**
	 * Register a new role
	 *
	 * @param string   $role     Role name
	 * @param string[] $extends  Roles extended
	 * @param int      $priority Role priority
	 *                           Roles with higher priority take precedence during resolution
	 *
	 * @return void
	 */
	public function register($role, array $extends = [], $priority = null) {
		if (in_array($role, $this->defaults)) {
			return;
		}

		if (!isset($priority)) {
			if (!empty($extends)) {
				$extended_priorities = array_map(function ($e) {
					return $this->$e->getPriority();
				}, $extends);

				$priority = max($extended_priorities) + 100;
			} else {
				$priority = 500;
			}
		}

		if (isset($this->roles[$role])) {
			$role = $this->roles[$role];
			foreach ($extends as $extend) {
				$role->addExtend($extend);
			}
			$role->setPriority($priority);
		} else {
			$this->roles[$role] = new Role($role, $extends, $priority);
		}
	}

	/**
	 * Unregister a role
	 *
	 * @param string $role Role name
	 *
	 * @return void
	 */
	public function unregister($role) {
		if (in_array($role, $this->defaults)) {
			return;
		}

		unset($this->roles[$role]);
	}

	/**
	 * Assign a role to a user
	 *
	 * @param string     $role   Role name
	 * @param ElggUser   $user   User
	 * @param ElggEntity $target Target entity (e.g. assign group admin role to a specific group)
	 *
	 * @return void
	 */
	public function assign($role, ElggUser $user, ElggEntity $target = null) {
		$role = $this->getRoleByName($role);
		if (!$role || !$role->isSelectable()) {
			return;
		}

		if (!isset($target)) {
			$target = elgg_get_site_entity();
		}

		add_entity_relationship($user->guid, "has_role:{$role->getRole()}", $target->guid);
	}

	/**
	 * Unassign a role from a user
	 *
	 * @param string     $role   Role name
	 * @param ElggUser   $user   User
	 * @param ElggEntity $target Target entity (e.g. assign group admin role to a specific group)
	 *
	 * @return void
	 */
	public function unassign($role, ElggUser $user, ElggEntity $target = null) {
		if (!isset($target)) {
			$target = elgg_get_site_entity();
		}

		remove_entity_relationship($user->guid, "has_role:{$role}", $target->guid);
	}

	/**
	 * Check if a user has an assigned role
	 *
	 * @param string     $role   Role name
	 * @param ElggUser   $user   User
	 * @param ElggEntity $target Target entity (e.g. assign group admin role to a specific group)
	 *
	 * @return Role|null
	 * @throws DatabaseException
	 */
	public function hasRole($role, ElggUser $user = null, ElggEntity $target = null) {
		$roles = $this->getRoles($user, $target);

		$roles = array_filter($roles, function (Role $e) use ($role) {
			return $e->getRole() == $role;
		});

		return array_shift($roles);
	}

	/**
	 * Get user's roles
	 *
	 * @param ElggUser|null   $user   User
	 * @param ElggEntity|null $target Target
	 *
	 * @return Role[]
	 * @throws DatabaseException
	 */
	public function getRoles(ElggUser $user = null, ElggEntity $target = null) {
		if (!isset($user)) {
			$user = elgg_get_logged_in_user_entity();
		}

		if (!isset($target)) {
			$target = elgg_get_site_entity();
		}

		$roles = [];

		if (!$user) {
			$roles[] = $this->guest;

			return $roles;
		}

		$qb = Select::fromTable('entity_relationships');
		$qb->select('relationship')
			->where($qb->merge([
				$qb->compare('guid_one', '=', $user->guid, ELGG_VALUE_INTEGER),
				$qb->compare('guid_two', '=', $target->guid, ELGG_VALUE_INTEGER),
				$qb->compare('relationship', 'LIKE', 'has_role:%', ELGG_VALUE_STRING),
			]))
			->groupBy('relationship');

		$relationships = elgg()->db->getData($qb);
		if ($relationships) {
			foreach ($relationships as $relationship) {
				list($prefix, $role) = explode(':', $relationship->relationship, 2);

				$roles[] = $this->$role;
			}
		}

		if (empty($roles) && $target instanceof \ElggSite) {
			if ($user->isAdmin()) {
				$roles[] = $this->admin;
			} else {
				$roles[] = $this->user;
			}
		}

		return $roles;
	}

	/**
	 * Get user roles for permissions check
	 *
	 * @param ElggUser|null $user   User
	 * @param ElggEntity    $target Target
	 *
	 * @return RoleInterface[]
	 * @throws DatabaseException
	 */
	public function getRolesForPermissionsCheck(ElggUser $user = null, $target = null) {

		if (!isset($user) && elgg_is_logged_in()) {
			$user = elgg_get_logged_in_user_entity();
		}

		if ($target instanceof ElggEntity) {
			// Get container roles
			$container_roles = $this->getRoles($user, $target);
		} else {
			$container_roles = [];
		}

		// Get site roles
		$site_roles = $this->getRoles($user);

		$roles = array_merge($container_roles, $site_roles);

		uasort($roles, function (RoleInterface $r1, RoleInterface $r2) {
			$p1 = $r1->getPriority();
			$p2 = $r2->getPriority();

			if ($p1 === $p2) {
				return 0;
			}

			return $p1 < $p2 ? -1 : 1;
		});

		return $roles;
	}

	/**
	 * Check if user can perform an action on a component
	 * Corresponds to capability configured using RoleInterface::on();
	 *
	 * @param string     $action    Action name
	 * @param string     $component Component name
	 * @param ElggEntity $container Target group
	 * @param Context    $context   Context definition
	 * @param bool       $default   Default permission
	 *
	 * @return bool
	 */
	public static function can($action, $component, $container = null, Context $context = null, $default = true) {

		if ($context) {
			$params = $context->getParams();
			$user = $context->getActor();
			$entity = $context->getTarget();
			if ($entity && !isset($container)) {
				$container = $entity->getContainerEntity();
			}
		} else {
			$params = [];
			$entity = null;
		}

		if (!isset($user)) {
			$user = elgg_get_logged_in_user_entity();
		}


		$params['user'] = $user;
		$params['container'] = $container;
		$params['entity'] = $entity;
		$params['action'] = $action;
		$params['component'] = $component;

		return elgg_trigger_plugin_hook('capability', "$action:$component", $params, $default);
	}

}