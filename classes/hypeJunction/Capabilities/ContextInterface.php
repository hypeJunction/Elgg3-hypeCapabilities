<?php

namespace hypeJunction\Capabilities;

interface ContextInterface {

	/**
	 * Get target of the capability check
	 * @return \ElggEntity|\ElggData|null
	 */
	public function getTarget();

	/**
	 * Get user performing an action
	 * @return ActorInterface
	 */
	public function getActor();

	/**
	 * Get additional parameters
	 * @return array
	 */
	public function getParams();

	/**
	 * Get a single parameter value
	 *
	 * @param string $name Parameter name
	 *
	 * @return mixed
	 */
	public function getParam($name);
}