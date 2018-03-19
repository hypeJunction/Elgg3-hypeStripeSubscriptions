<?php

return [
	'subscriptions.stripe' => \DI\object(\hypeJunction\Subscriptions\Stripe\StripeSubscriptionsService::class)
		->constructor(\DI\get('stripe')),

	'subscriptions.gateways.stripe' => \DI\object(\hypeJunction\Subscriptions\Stripe\StripeRecurringPaymentGateway::class)
		->constructor(\DI\get('stripe')),

];