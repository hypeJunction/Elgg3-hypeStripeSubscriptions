<?php

namespace hypeJunction\Subscriptions\Stripe;

use Elgg\Database\QueryBuilder;
use Elgg\Http\ResponseBuilder;
use Elgg\Request;
use hypeJunction\Subscriptions\SubscriptionPlan;

class ImportStripePlans {

	/**
	 * Import stripe subscriptions
	 *
	 * @param Request $request
	 *
	 * @return ResponseBuilder
	 * @throws \Exception
	 */
	public function __invoke(Request $request) {

		$stripe = elgg()->stripe;
		/* @var $stripe \hypeJunction\Stripe\StripeClient */

		$stripe_subscriptions = elgg()->{'subscriptions.stripe'};
		/* @var $stripe_subscriptions \hypeJunction\Subscriptions\Stripe\StripeSubscriptionsService */

		$limit = 100;

		$has_more = true;
		$last_id = null;

		$imported = 0;
		$exported = 0;

		while ($has_more) {
			$collection = $stripe->getPlans([
				'limit' => $limit,
				'starting_after' => $last_id,
			]);

			foreach ($collection->data as $plan) {
				$stripe_subscriptions->importPlan($plan);
				$last_id = $plan->id;
				$imported++;
			}

			$has_more = $collection->has_more;
		}

		elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use (&$exported, $stripe_subscriptions) {
			$plans = elgg_get_entities([
				'types' => 'object',
				'subtypes' => SubscriptionPlan::SUBTYPE,
				'wheres' => function (QueryBuilder $qb) {
					$qb->joinMetadataTable('e', 'guid', 'stripe_id', 'left', 'stripe_id');

					return $qb->compare('stripe_id.value', 'IS NULL');
				},
				'batch' => true,
				'limit' => 0,
				'batch_inc_offset' => false,
			]);

			foreach ($plans as $plan) {
				/* @var $plan SubscriptionPlan */

				try {
					$stripe_subscriptions->exportPlan($plan);

					$exported++;
				} catch (\Exception $ex) {
					register_error($ex->getMessage());

					return false;
				}
			}
		});

		return elgg_ok_response('', elgg_echo('subscriptions:stripe:import:success', [$imported, $exported]));
	}


}