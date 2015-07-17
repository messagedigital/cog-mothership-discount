<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Cog\Bootstrap\RoutesInterface;

class Routes implements RoutesInterface
{
	public function registerRoutes($router)
	{
		$router['ms.discount']->add('ms.discount.process', '/discount/add', 'Message:Mothership:Discount::Controller:AddDiscount#discountProcess')
			->setMethod('POST');


		$router['ms.cp.discount']->setParent('ms.cp')->setPrefix('/discount');
		$router['ms.cp.discount']->add('ms.cp.discount.dashboard', '', 'Message:Mothership:Discount::Controller:Dashboard#index');

		$router['ms.cp.discount']->add('ms.cp.discount.sidebar.search.code.action', 'search/code', 'Message:Mothership:Discount::Controller:Sidebar#searchCodeAction')
			->setMethod('POST');

		$router['ms.cp.discount']->add('ms.cp.discount.sidebar.search.date.action', 'search/date', 'Message:Mothership:Discount::Controller:Sidebar#searchDateAction');

		$router['ms.cp.discount']->add('ms.cp.discount.listing.all', 'listing/all', 'Message:Mothership:Discount::Controller:Listing#all');
		$router['ms.cp.discount']->add('ms.cp.discount.listing.active', 'listing/active', 'Message:Mothership:Discount::Controller:Listing#active');
		$router['ms.cp.discount']->add('ms.cp.discount.listing.active.date', 'listing/active/from/{fromTimestamp}/to/{toTimestamp}', 'Message:Mothership:Discount::Controller:Listing#active');
		$router['ms.cp.discount']->add('ms.cp.discount.listing.inactive', 'listing/inactive', 'Message:Mothership:Discount::Controller:Listing#inactive');

		$router['ms.cp.discount']->add('ms.cp.discount.create', 'create', 'Message:Mothership:Discount::Controller:Create#index');

		$router['ms.cp.discount']->add('ms.cp.discount.delete', '/{discountID}/delete', 'Message:Mothership:Discount::Controller:Detail#delete')
			->setRequirement('discountID', '\d+')
			->setMethod('DELETE');

		$router['ms.cp.discount']->add('ms.cp.discount.restore', '/{discountID}/restore/{hash}', 'Message:Mothership:Discount::Controller:Detail#restore')
			->setRequirement('discountID', '\d+')
			->setMethod('GET')
			->enableCsrf('hash');

		$router['ms.cp.discount']->add('ms.cp.discount.edit', 'edit/{discountID}', 'Message:Mothership:Discount::Controller:Detail#attributes')
			->setRequirement('discountID', '\d+');

		$router['ms.cp.discount']->add('ms.cp.discount.edit.criteria', 'edit/{discountID}/criteria', 'Message:Mothership:Discount::Controller:Detail#criteria')
			->setRequirement('discountID', '\d+');

		$router['ms.cp.discount']->add('ms.cp.discount.edit.benefit', 'edit/{discountID}/benefit', 'Message:Mothership:Discount::Controller:Detail#benefit')
			->setRequirement('discountID', '\d+');

		$router['ms.cp.discount']->add('ms.cp.discount.view.orders', 'view/{discountID}/orders', 'Message:Mothership:Discount::Controller:Detail#orders');

		// Bundles
		$router['ms.cp.discount']->add('ms.cp.discount.bundle.create.action', 'bundle/create', 'Message:Mothership:Discount::Controller:Bundle#createAction')
			->setMethod('POST')
		;

		$router['ms.cp.discount']->add('ms.cp.discount.bundle.create', 'bundle/create', 'Message:Mothership:Discount::Controller:Bundle#create');

		$router['ms.cp.discount']->add('ms.cp.discount.bundle.edit.action', 'bundle/{bundleID}/edit', 'Message:Mothership:Discount::Controller:Bundle#editAction')
			->setRequirement('bundleID', '\d+')
			->setMethod('POST')
		;

		$router['ms.cp.discount']->add('ms.cp.discount.bundle.edit', 'bundle/{bundleID}/edit', 'Message:Mothership:Discount::Controller:Bundle#edit');
		$router['ms.product.basket']->add('ms.product.basket.add_bundle', 'basket/bundle/add/{bundleID}', 'Message:Mothership:Discount::Controller:Module:Bundle:ProductSelector#addBundle')
			->setRequirement('bundleID', '\d+')
			->setMethod('POST')
		;
	}
}