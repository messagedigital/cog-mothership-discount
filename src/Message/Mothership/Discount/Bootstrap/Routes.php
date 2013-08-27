<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Cog\Bootstrap\RoutesInterface;

class Routes implements RoutesInterface
{
	public function registerRoutes($router)
	{
		$router['ms.discount']->setParent('ms.cp')->setPrefix('/discount');
		$router['ms.discount']->add('ms.discount.dashboard', '', '::Controller:Dashboard#index');

		$router['ms.discount']->add('ms.discount.listing.all', 'all', '::Controller:Listing#all');

		$router['ms.discount']->add('ms.discount.create.action', 'create', '::Controller:Create#process')
			->setMethod('POST');
		$router['ms.discount']->add('ms.discount.create', 'create', '::Controller:Create#index');
	}
}