<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Cog\Bootstrap\RoutesInterface;

class Routes implements RoutesInterface
{
	public function registerRoutes($router)
	{
		$router['ms.discount']->add('ms.discount.process', '/discount/add', '::Controller:AddDiscount#discountProcess')
			->setMethod('POST');


		$router['ms.cp.discount']->setParent('ms.cp')->setPrefix('/discount');
		$router['ms.cp.discount']->add('ms.cp.discount.dashboard', '', '::Controller:Dashboard#index');

		$router['ms.cp.discount']->add('ms.cp.discount.sidebar.search.code.action', 'search/code', '::Controller:Sidebar#searchCodeAction')
			->setMethod('POST');

		$router['ms.cp.discount']->add('ms.cp.discount.sidebar.search.date.action', 'search/date', '::Controller:Sidebar#searchDateAction');

		$router['ms.cp.discount']->add('ms.cp.discount.listing.all', 'listing/all', '::Controller:Listing#all');
		$router['ms.cp.discount']->add('ms.cp.discount.listing.active', 'listing/active', '::Controller:Listing#active');
		$router['ms.cp.discount']->add('ms.cp.discount.listing.active.date', 'listing/active/from/{fromTimestamp}/to/{toTimestamp}', '::Controller:Listing#active');
		$router['ms.cp.discount']->add('ms.cp.discount.listing.inactive', 'listing/inactive', '::Controller:Listing#inactive');

		$router['ms.cp.discount']->add('ms.cp.discount.create.action', 'create', '::Controller:Create#process')
			->setMethod('POST');
		$router['ms.cp.discount']->add('ms.cp.discount.create', 'create', '::Controller:Create#index');


		$router['ms.cp.discount']->add('ms.cp.discount.delete', '/{discountID}/delete', '::Controller:Detail#delete')
			->setRequirement('discountID', '\d+')
			->setMethod('DELETE');

		$router['ms.cp.discount']->add('ms.cp.discount.restore', '/{discountID}/restore/{hash}', '::Controller:Detail#restore')
			->setRequirement('discountID', '\d+')
			->setMethod('GET')
			->enableCsrf('hash');

		$router['ms.cp.discount']->add('ms.cp.discount.edit.action', 'edit/{discountID}', '::Controller:Detail#processAttributes')
			->setRequirement('discountID', '\d+')
			->setMethod('POST');
		$router['ms.cp.discount']->add('ms.cp.discount.edit', 'edit/{discountID}', '::Controller:Detail#attributes')
			->setRequirement('discountID', '\d+');

		$router['ms.cp.discount']->add('ms.cp.discount.edit.criteria.action', 'edit/{discountID}/criteria', '::Controller:Detail#processCriteria')
			->setRequirement('discountID', '\d+')
			->setMethod('POST');
		$router['ms.cp.discount']->add('ms.cp.discount.edit.criteria', 'edit/{discountID}/criteria', '::Controller:Detail#criteria')
			->setRequirement('discountID', '\d+');

		$router['ms.cp.discount']->add('ms.cp.discount.edit.benefit.action', 'edit/{discountID}/benefit', '::Controller:Detail#processBenefit')
			->setRequirement('discountID', '\d+')
			->setMethod('POST');
		$router['ms.cp.discount']->add('ms.cp.discount.edit.benefit', 'edit/{discountID}/benefit', '::Controller:Detail#benefit')
			->setRequirement('discountID', '\d+');

		$router['ms.cp.discount']->add('ms.cp.discount.view.orders', 'view/{discountID}/orders', '::Controller:Detail#orders');

	}
}