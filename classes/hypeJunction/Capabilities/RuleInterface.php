<?php

namespace hypeJunction\Capabilities;

interface RuleInterface {

	/**
	 * Set rule context
	 *
	 * @param Context $context Context
	 *
	 * @return void
	 */
	public function setContext(Context $context);

	/**
	 * Check if this rule grants permission to the actor
	 *
	 * @param bool $current Current permission
	 *
	 * @return bool
	 */
	public function grants($current = null);

}