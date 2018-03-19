<?php

namespace hypeJunction\Subscriptions\Stripe;

use Elgg\Hook;

class DigestSubscriptionUpdateHook {

	/**
	 * Digest plan created web hook
	 *
	 * @param Hook $hook Hook
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function __invoke(Hook $hook) {

		$stripe_event = $hook->getParam('event');
		/* @var $stripe_event \Stripe\Event */

		$subscription = $stripe_event->data->object;

		$svc = elgg()->{'subscriptions.stripe'};

		/* @var $svc \hypeJunction\Subscriptions\Stripe\StripeSubscriptionsService */

		return $svc->importSubscription($subscription);
	}
}