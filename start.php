<?php

/**
 * Subscriptions
 *
 * @author Ismayil Khayredinov <info@hypejunction.com>
 */
require_once __DIR__ . '/autoloader.php';

return function () {
	elgg_register_event_handler('init', 'system', function () {

		$svc = elgg()->subscriptions;
		/* @var $svc \hypeJunction\Subscriptions\SubscriptionsService */

		$svc->registerGateway(elgg()->{'subscriptions.gateways.stripe'});

		elgg_register_event_handler('update', 'object', \hypeJunction\Subscriptions\Stripe\OnUpdateEvent::class);
		elgg_register_event_handler('delete', 'object', \hypeJunction\Subscriptions\Stripe\OnDeleteEvent::class);

		elgg_register_event_handler('cancel', 'subscription', \hypeJunction\Subscriptions\Stripe\OnSubscriptionCancelEvent::class);

		elgg_register_plugin_hook_handler('register', 'menu:page', \hypeJunction\Subscriptions\Stripe\PageMenu::class);

		elgg_register_plugin_hook_handler('plan.created', 'stripe', \hypeJunction\Subscriptions\Stripe\DigestPlanUpdateHook::class);
		elgg_register_plugin_hook_handler('plan.updated', 'stripe', \hypeJunction\Subscriptions\Stripe\DigestPlanUpdateHook::class);
		elgg_register_plugin_hook_handler('plan.deleted', 'stripe', \hypeJunction\Subscriptions\Stripe\DigestPlanDeleteHook::class);

		elgg_register_plugin_hook_handler('customer.subscription.created', 'stripe', \hypeJunction\Subscriptions\Stripe\DigestSubscriptionUpdateHook::class);
		elgg_register_plugin_hook_handler('customer.subscription.updated', 'stripe', \hypeJunction\Subscriptions\Stripe\DigestSubscriptionUpdateHook::class);
		elgg_register_plugin_hook_handler('customer.subscription.deleted', 'stripe', \hypeJunction\Subscriptions\Stripe\DigestSubscriptionUpdateHook::class);
	});
};
