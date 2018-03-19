<?php

namespace hypeJunction\Subscriptions\Stripe;

use Elgg\Hook;
use ElggMenuItem;

class PageMenu {

	/**
	 * Setup page menu
	 *
	 * @elgg_plugin_hook register menu:page
	 *
	 * @param Hook $hook Hook
	 *
	 * @return ElggMenuItem[]|null
	 */
	public function __invoke(Hook $hook) {

		$menu = $hook->getValue();
		/* @var $menu ElggMenuItem[] */

		if (elgg_in_context('admin')) {
			$menu[] = ElggMenuItem::factory([
				'name' => 'subscriptions:stripe:import',
				'parent_name' => 'subscriptions',
				'href' => elgg_generate_action_url('subscriptions/stripe/import'),
				'text' => elgg_echo('subscriptions:stripe:import'),
				'icon' => 'import',
				'section' => 'configure',
				'confirm' => true,
			]);
		}

		return $menu;
	}
}