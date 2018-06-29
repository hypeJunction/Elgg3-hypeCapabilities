<?php

namespace hypeJunction\Capabilities;

use ElggData;
use ElggEntity;
use ElggUser;

class Context implements ContextInterface {

	/**
	 * @var ElggData|null
	 */
	protected $target;

	/**
	 * @var ElggUser|null
	 */
	protected $actor;

	/**
	 * @var array
	 */
	protected $params;

	/**
	 * Constructor
	 *
	 * @param ElggEntity|null $target Target
	 * @param ElggUser|null   $actor  Actor
	 * @param array           $params Additional params
	 */
	public function __construct(ElggEntity $target = null, ElggUser $actor = null, array $params = []) {
		$this->target = $target;
		if (!isset($actor) && elgg_is_logged_in()) {
			$actor = elgg_get_logged_in_user_entity();
		}
		$this->actor = $actor;
		$this->params = $params;
	}

	/**
	 * Get target of the capability check
	 * @return ElggEntity|null
	 */
	public function getTarget() {
		return $this->target;
	}

	/**
	 * Get user performing an action
	 * @return ElggUser
	 */
	public function getActor() {
		return $this->actor;
	}

	/**
	 * Get additional parameters
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * Get a single parameter value
	 *
	 * @param string $name    Parameter name
	 * @param mixed  $default Default value
	 *
	 * @return mixed
	 */
	public function getParam($name, $default = null) {
		return elgg_extract($name, $this->params, $default);
	}
}