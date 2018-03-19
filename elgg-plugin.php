<?php

return [
	'actions' => [
		'subscriptions/stripe/import' => [
			'controller' => \hypeJunction\Subscriptions\Stripe\ImportStripePlans::class,
			'access' => 'admin',
		],
	],
];
