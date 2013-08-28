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

		$router['ms.discount']->add('ms.discount.edit.action', 'edit/{discountID}', '::Controller:Edit#processAttributes')
			->setRequirement('discountID', '\d+')
			->setMethod('POST');
		$router['ms.discount']->add('ms.discount.edit', 'edit/{discountID}', '::Controller:Edit#index')
			->setRequirement('discountID', '\d+');

		$router['ms.discount']->add('ms.discount.edit.products.action', 'edit/{discountID}/products', '::Controller:Edit#processProducts')
			->setRequirement('discountID', '\d+')
			->setMethod('POST');
		$router['ms.discount']->add('ms.discount.edit.products', 'edit/{discountID}/products', '::Controller:Edit#products')
			->setRequirement('discountID', '\d+');

		$router['ms.discount']->add('ms.discount.edit.discount-details.action', 'edit/{discountID}/discount-details', '::Controller:Edit#processDiscountDetails')
			->setRequirement('discountID', '\d+')
			->setMethod('POST');
		$router['ms.discount']->add('ms.discount.edit.discount-details', 'edit/{discountID}/discount-details', '::Controller:Edit#discountDetails')
			->setRequirement('discountID', '\d+');

	}
}