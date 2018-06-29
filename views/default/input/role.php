<?php

$svc = elgg()->roles;
/* @var $svc \hypeJunction\Capabilities\Roles */

$roles = $svc->all(true);

$options_values = [];

foreach ($roles as $role) {
	$options_values[$role->getRole()] = $role->getLabel();
}

$vars['options_values'] = $options_values;

echo elgg_view('input/select', $vars);