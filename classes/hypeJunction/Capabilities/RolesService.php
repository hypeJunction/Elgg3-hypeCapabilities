<?php

namespace hypeJunction\Capabilities;

use Elgg\Database\Select;
use ElggEntity;
use ElggUser;

/**
 * @property-read Role $user
 * @property-read Role $admin
 * @property-read Role $guest
 */
class RolesService {

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
	 * Register a new role
	 *
	 * @param string   $role    Role name
	 * @param string[] $extends Roles extended
	 *
	 * @return void
	 */
	public function register($role, array $extends = []) {
		if (in_array($role, $this->defaults)) {
			return;
		}

		$this->roles[$role] = new Role($role, $extends);
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
		if (in_array($role, $this->defaults)) {
			return;
		}

		if (!isset($target)) {
			$target = elgg_get_site_entity();
		}

		add_entity_relationship($user->guid, "has_role:{$role}", $target->guid);
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
		if (in_array($role, $this->defaults)) {
			return;
		}

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

		if ($user->isAdmin()) {
			$roles[] = $this->admin;
		} else {
			$roles[] = $this->user;
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

		return $roles;
	}
}