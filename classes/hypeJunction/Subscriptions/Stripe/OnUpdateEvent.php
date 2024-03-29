<?php

namespace hypeJunction\Subscriptions\Stripe;

use Elgg\Event;
use hypeJunction\Subscriptions\SubscriptionPlan;
use Psr\Log\LogLevel;

class OnUpdateEvent {

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

		try {
			$subs = elgg()->{'subscriptions.stripe'};
			/* @var $subs \hypeJunction\Subscriptions\Stripe\StripeSubscriptionsService */

			$subs->exportPlan($entity);
		} catch (\Exception $ex) {
			elgg_log($ex, LogLevel::ERROR);

			register_error($ex->getMessage());
		}

	}
}