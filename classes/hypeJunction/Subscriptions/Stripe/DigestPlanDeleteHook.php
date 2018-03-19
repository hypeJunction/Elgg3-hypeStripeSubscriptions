<?php

namespace hypeJunction\Subscriptions\Stripe;

use Elgg\Hook;

class DigestPlanDeleteHook {

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

		$plan = $stripe_event->data->object;

		$svc = elgg()->{'subscriptions.stripe'};

		/* @var $svc \hypeJunction\Subscriptions\Stripe\StripeSubscriptionsService */

		return $svc->deletePlan($plan);
	}
}