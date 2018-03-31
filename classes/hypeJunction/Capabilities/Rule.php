<?php

namespace hypeJunction\Capabilities;

class Rule implements RuleInterface {

	/**
	 * @var bool
	 */
	protected $grant;

	/**
	 * @var callable|null|string
	 */
	protected $condition;

	/**
	 * @var Context
	 */
	protected $context;

	/**
	 * Constructor
	 *
	 * @param bool            $grant     Rule
	 * @param string|callable $condition Condition
	 */
	public function __construct($grant, $condition = null) {
		$this->grant = $grant;
		$this->condition = $condition;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setContext(Context $context) {
		$this->context = $context;
	}

	/**
	 * {@inheritdoc}
	 */
	public function grants($current = null) {
		switch ($this->condition) {
			case Role::STACK :
				if ($this->grant == Role::ALLOW) {
					if (!isset($current) || $current === true) {
						// Only allow if other permissions are met
						return true;
					}
				}

				return false;

			case Role::OVERRIDE :
				return $this->grant;
		}

		if (is_callable($this->condition)) {
			$grant = elgg()->call($this->condition, [$this->context, $this]);
			return isset($grant) ? $grant : $current;
		}

		return $current;
 	}

}