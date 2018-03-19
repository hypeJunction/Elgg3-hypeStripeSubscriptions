<?php

namespace hypeJunction\Subscriptions\Stripe;

use Elgg\Event;
use hypeJunction\Subscriptions\SubscriptionPlan;

class OnDeleteEvent {

	/**
	 * Sync plan updates
	 *
	 * @param Event $event Event
	 *
	 * @return bool|null
	 */
	public function __invoke(Event $event) {

		$entity = $event->getObject();
		if (!$entity instanceof SubscriptionPlan) {
			return null;
		}

		if ($entity->getVolatileData('is_import')) {
			return null;
		}

		$svc = elgg()->stripe;
		/* @var $svc \hypeJunction\Stripe\StripeClient */

		$plan_id = $entity->plan_id;

		try {
			if ($entity->stripe_id) {
				$plan = $svc->getPlan($plan_id);
				$plan->delete();
			}
		} catch (\Exception $ex) {

		}

	}
}