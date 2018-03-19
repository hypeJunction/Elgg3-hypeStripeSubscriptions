<?php

namespace hypeJunction\Subscriptions\Stripe;

use hypeJunction\Payments\Amount;
use hypeJunction\Stripe\StripeClient;
use hypeJunction\Subscriptions\SubscriptionPlan;
use Stripe\Plan;
use Stripe\Product;
use Stripe\Subscription;

class StripeSubscriptionsService {

	/**
	 * @var StripeClient
	 */
	protected $client;

	/**
	 * Constructor
	 *
	 * @param StripeClient $client Client
	 */
	public function __construct(StripeClient $client) {
		$this->client = $client;
	}

	/**
	 * Import plan
	 *
	 * @param Plan $plan Plan
	 *
	 * @return SubscriptionPlan|false
	 * @throws \Exception
	 */
	public function importPlan(Plan $plan) {

		return elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($plan) {
			$entities = elgg_get_entities([
				'types' => 'object',
				'subtypes' => SubscriptionPlan::SUBTYPE,
				'metadata_name_value_pairs' => [
					'stripe_id' => $plan->id,
				],
				'limit' => 1,
			]);

			if (empty($entities)) {
				$entity = new SubscriptionPlan();
				$entity->container_guid = elgg_get_site_entity()->guid;
				$entity->access_id = ACCESS_PUBLIC;
			} else {
				$entity = array_shift($entities);
			}

			$product = Product::retrieve($plan->product);

			$entity->title = $product->name;

			$entity->stripe_id = $plan->id;

			$entity->setPlanId($plan->id);

			$entity->interval = $plan->interval;
			$entity->interval_count = $plan->interval_count;

			$amount = new Amount($plan->amount, $plan->currency);
			$entity->setPrice($amount);

			$entity->trial_period_days = $plan->trial_period_days;

			$entity->setVolatileData('is_import', true);

			if (!$entity->save()) {
				return false;
			}

			$plan->metadata = [
				'guid' => $entity->guid,
			];

			$plan->save();

			return $entity;
		});

	}

	/**
	 * Create/update a plan
	 *
	 * @param SubscriptionPlan $plan Plan
	 *
	 * @return SubscriptionPlan
	 * @throws \Exception
	 */
	public function exportPlan(SubscriptionPlan $plan) {
		return elgg_call(ELGG_IGNORE_ACCESS, function () use ($plan) {
			if ($plan->stripe_id) {
				$stripe_plan = $this->client->getPlan($plan->plan_id);
				$stripe_plan->nickname = $plan->title;
				$stripe_plan->save();
			} else {
				$stripe_plan = $this->client->createPlan([
					'product' => [
						'name' => $plan->title,
						'metadata' => [
							'guid' => $plan->guid,
						],
					],
					'id' => $plan->plan_id,
					'amount' => $plan->getTotalPrice()->getAmount(),
					'currency' => $plan->getTotalPrice()->getCurrency(),
					'interval' => $plan->interval,
					'interval_count' => $plan->interval_count,
					'metadata' => [
						'guid' => $plan->guid,
					],
				]);
			}

			$plan->stripe_id = $stripe_plan->id;

			return $plan;
		});
	}

	/**
	 * Delete a plan
	 *
	 * @param Plan $plan Plan
	 *
	 * @return int[]
	 * @throws \Exception
	 */
	public function deletePlan(Plan $plan) {

		return elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($plan) {
			$entities = elgg_get_entities([
				'types' => 'object',
				'subtypes' => SubscriptionPlan::SUBTYPE,
				'metadata_name_value_pairs' => [
					'stripe_id' => $plan->id,
				],
				'limit' => 0,
				'batch' => true,
				'batch_inc_offset' => false,
			]);

			$result = [];

			foreach ($entities as $entity) {
				if ($entity->delete()) {
					$result[] = $entity->guid;
				}
			}

			return $result;
		});

	}

	/**
	 * Import stripe subscription
	 *
	 * @param Subscription $subscription Subscription
	 *
	 * @return \hypeJunction\Subscriptions\Subscription|false
	 * @throws \Exception
	 */
	public function importSubscription(Subscription $subscription) {

		return elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($subscription) {

			$entities = elgg_get_entities([
				'types' => 'object',
				'subtypes' => \hypeJunction\Subscriptions\Subscription::SUBTYPE,
				'metadata_name_value_pairs' => [
					'stripe_id' => $subscription->id,
				],
				'limit' => 1,
			]);

			$entity = false;

			if ($entities) {
				$entity = $entities[0];
			} else {
				try {
					$metadata = $subscription->plan->metadata->values();
					$plan_guid = elgg_extract('guid', $metadata);

					$plan = get_entity($plan_guid);

					$customer_id = $subscription->customer;
					$customer = $this->client->getCustomer($customer_id);

					$users = get_user_by_email($customer->email);

					if ($plan instanceof SubscriptionPlan && $users) {
						$user = array_shift($users);

						$entity = $plan->subscribe($user, $subscription->current_period_end);
					}
				} catch (\Exception $ex) {

				}
			}

			if (!$entity) {
				return true;
			}

			$entity->cancelled_at = $subscription->canceled_at;

			if ($subscription->ended_at) {
				$entity->current_period_end = $subscription->ended_at;
			} else {
				$entity->current_period_end = $subscription->current_period_end;
			}

			$entity->stripe_id = $subscription->id;

			return $entity;
		});
	}
}