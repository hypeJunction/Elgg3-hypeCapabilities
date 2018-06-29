hypeCapabilities
================

Capabilities and roles API


## Registering a role

```php
\hypeJunction->register('role_name');
```

## Assigning a role to a user

```php
// Site wide role
elgg()->roles->assign('role_name', $user);

// Group specific role
elgg()->roles->assign('role_name', $user, $group);
```

## Remove a role from a user

```php
// Site wide role
elgg()->roles->unassign('role_name', $user);

// Group specific role
elgg()->roles->unassign('role_name', $user, $group);
```

## Configuring role permissions

### Creating entities of a specific type

```php

// Prevent users with given role from creating entities of a given type
elgg()->roles->role_name->onCreate('object', 'blog', Role::DENY);

// Allow users to create entities of a given type regardless of context
elgg()->roles->role_name->onCreate('object', 'blog', Role::ALLOW, Role::OVERRIDE);

// Allow users to create entities of a given type if all other container permissins are met
elgg()->roles->role_name->onCreate('object', 'blog', Role::ALLOW, Role::STACK);

// Allow users to create entities when specific conditions are met
// Only allow group blogs
elgg()->roles->role_name->onCreate('object', 'blog', Role::DENY, function(\hypeJunction\Capabilities\Context $context) {
	$container = $context->getTarget();
	if (!$container instanceof ElggGroup) {
		return Role::DENY;
	}
});
```

### Update and delete permissions

Similar to above, you can use ```onUpdate``` and ```onDelete``` methods;

### Granting administrative permissions 

Administrative permissions imply high-level administrative action on entities, e.g. approving a certain post after moderation.
By default, core does not use this privilege level, but you can check if the user has admin permissions over an entity like so:

```php
$params = [
	'entity' => $entity,
	'user' => $user,
];
if (!elgg_trigger_plugin_hook('permissions_check:administer', "$entity->type:$entity->subtype", $params, false)) {
	// No permissions to approve
	throw new EntityPermissionsException();
}

// Do something that requires high level permissions, e.g.
$entity->published_status = 'published';
```

Granting/denying admin permissions

```php
// Prevent users with given role from creating entities of a given type
// Allow moderator role to administer all blogs regardless of owner/container
elgg()->roles->moderator->onAdminister('object', 'blog', Role::ALLOW, Role::OVERRIDE);

// Allow users to create entities when specific conditions are met
// Allow teacher to administer all group blogs
elgg()->roles->teacher->canAdminister('object', 'blog', Role::ALLOW, function(\hypeJunction\Capabilities\Context $context) {
	$entity = $context->getTarget();
	$actor = $context->getActor();
	
	$container = $entity->getContainerEntity();
	return $container->canEdit($actor->guid);
});
```


### Routes

You can allow/deny access to certain routes by route name

```php
// Context parameter contain matched route elements
// e.g. prevent access to user profile if users are not friends
elgg()->roles->user->onRouteAccess('view:user', Role::DENY, function(\hypeJunction\Capabilities\Context $context) {
	$actor = $context->getActor();

	$username = $context->getParam('username');
	$user = get_user_by_username($username);

	if (!$actor || !$user instanceof ElggUser || !$actor->isFriendOf($user->guid)) {
		register_error('You must be friends to access user profiles');
		return Role::DENY;
	}
});

// Here is an example of how to prevent access to member pages to non-logged in users:
elgg()->roles->guest->onRouteAccess('collection:user:user', Role::DENY);
elgg()->roles->guest->onRouteAccess('collection:user:user:alpha', Role::DENY);
elgg()->roles->guest->onRouteAccess('collection:user:user:newest', Role::DENY);
elgg()->roles->guest->onRouteAccess('collection:user:user:online', Role::DENY);
elgg()->roles->guest->onRouteAccess('collection:user:user:popular', Role::DENY);
elgg()->roles->guest->onRouteAccess('search:user:user', Role::DENY);
elgg()->roles->guest->onRouteAccess('view:user', Role::DENY);
```


### Custom (component) capabilities

You can check and alter custom capabilities:

```php
// Check a custom role
elgg()->roles->can('read', 'discussions');

// Define how role responds to capability check
elgg()->roles->guest->on('read', 'discussions', Role::DENY);

// Override role response
elgg_register_plugin_hook_handler('capability', 'read:discussions', function(Hook $hook) {

});
```