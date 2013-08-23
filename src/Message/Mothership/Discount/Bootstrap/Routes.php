<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Cog\Bootstrap\RoutesInterface;

class Routes implements RoutesInterface
{
	public function registerRoutes($router)
	{
		$router['ms.discount']->setParent('ms.cp')->setPrefix('/discount');
		$router['ms.discount']->add('ms.discount.dashboard', '', '::Controller:Discount:Dashboard#index');

	}
}