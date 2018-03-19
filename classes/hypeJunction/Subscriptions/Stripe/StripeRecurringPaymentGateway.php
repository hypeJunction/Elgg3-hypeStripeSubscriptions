<?php

namespace hypeJunction\Subscriptions\Stripe;

use Elgg\Http\ResponseBuilder;
use ElggUser;
use hypeJunction\Payments\Amount;
use hypeJunction\Stripe\StripeGateway;
use hypeJunction\Subscriptions\RecurringPaymentGatewayInterface;
use hypeJunction\Subscriptions\Subscription;
use hypeJunction\Subscriptions\SubscriptionPlan;

class StripeRecurringPaymentGateway extends StripeGateway implements RecurringPaymentGatewayInterface {

	/**
	 * Start a recurring payment
	 *
	 * @param ElggUser         $user   User
	 * @param SubscriptionPlan $plan   Plan
	 * @param array            $params Request parameters
	 *
	 * @return ResponseBuilder
	 */
	public function subscribe(ElggUser $user, SubscriptionPlan $plan, array $params = []) {
		$token = elgg_extract('stripe_token', $params);

		if (!$token) {
			return elgg_error_response(elgg_echo('subscriptions:stripe:error:payment_required'), REFERRER, ELGG_HTTP_BAD_REQUEST);
		}

		try {
			$customer = $this->client->createCustomer($user);
			$customer->sources->create([
				'source' => $token,
			]);

			$subscription = $this->client->createSubscription([
				'customer' => $customer->id,
				'items' => [
					[
						'plan' => $plan->stripe_id,
					],
				],
			]);

			if ($record = $plan->subscribe($user, $subscription->current_period_end)) {
				$record->stripe_id = $subscription->id;

				return elgg_ok_response([
					'user' => $user,
					'subscription' => $record,
				], elgg_echo('subscriptions:subscribe:success', [$plan->getDisplayName()]));
			}

		} catch (\Exception $ex) {
			return elgg_error_response($ex->getMessage(), REFERRER, $ex->getCode() ? : ELGG_HTTP_INTERNAL_SERVER_ERROR);
		}

		return elgg_error_response(elgg_echo('subscriptions:subscribe:error'), REFERRER, ELGG_HTTP_INTERNAL_SERVER_ERROR);
	}

	/**
	 * Cancel subscription
	 *
	 * @param Subscription $subscription Subscription
	 * @param array        $params       Request parameters
	 *
	 * @return bool
	 */
	public function cancel(Subscription $subscription, array $params = []) {

		$at_period_end = elgg_extract('at_period_end', $params, true);

		try {

			$stripe_subscription = $this->client->getSubscription($subscription->stripe_id);

			if (!$at_period_end) {
				$time = new \DateTime('now', new \DateTimeZone('UTC'));
				$used = $time->getTimestamp() - $stripe_subscription->current_period_start;

				$duration = $stripe_subscription->current_period_end - $stripe_subscription->current_period_start;

				$refund = $stripe_subscription->plan->amount - round(($used / $duration) * $stripe_subscription->plan->amount);

				if ($refund > 0) {
					try {
						$invoices = $this->client->getInvoices([
							'subscription' => $stripe_subscription->id,
						]);

						if ($invoices->count()) {
							$invoice = $invoices->data[0];

							$this->client->createRefund([
								'charge' => $invoice->charge,
								'amount' => $refund,
							]);

							$amount = new Amount($refund, $stripe_subscription->currency);

							system_message(elgg_echo('subscriptions:stripe:refunded', [
								$amount->getConvertedAmount(),
								$amount->getCurrency()
							]));
						}
					} catch (\Exception $ex) {
						elgg_log($ex->getMessage(), 'ERROR');
					}
				}
			}

			$stripe_subscription->cancel(['at_period_end' => $at_period_end]);

			return true;
		} catch (\Exception $ex) {
			elgg_log($ex->getMessage(), 'ERROR');

			return false;
		}
	}
}