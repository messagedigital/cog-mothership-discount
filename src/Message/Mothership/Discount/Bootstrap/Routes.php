<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Cog\Bootstrap\RoutesInterface;

class Routes implements RoutesInterface
{
	public function registerRoutes($router)
	{
		$router['ms.discount']->setParent('ms.cp')->setPrefix('/discount');
		$router['ms.discount']->add('ms.discount.dashboard', '', '::Controller:Dashboard#index');

		$router['ms.discount']->add('ms.discount.sidebar.search.id.action', 'search/id', '::Controller:Sidebar#searchIDAction')
			->setMethod('POST');

		$router['ms.discount']->add('ms.discount.sidebar.search.date.action', 'search/date', '::Controller:Sidebar#searchDateAction');

		$router['ms.discount']->add('ms.discount.listing.all', 'listing/all', '::Controller:Listing#all');
		$router['ms.discount']->add('ms.discount.listing.active', 'listing/active', '::Controller:Listing#active');
		$router['ms.discount']->add('ms.discount.listing.active.date', 'listing/active/from/{fromTimestamp}/to/{toTimestamp}', '::Controller:Listing#active');
		$router['ms.discount']->add('ms.discount.listing.inactive', 'listing/inactive', '::Controller:Listing#inactive');

		$router['ms.discount']->add('ms.discount.create.action', 'create', '::Controller:Create#process')
			->setMethod('POST');
		$router['ms.discount']->add('ms.discount.create', 'create', '::Controller:Create#index');


		$router['ms.discount']->add('ms.discount.delete', '/{discountID}/delete', '::Controller:Detail#delete')
			->setRequirement('discountID', '\d+')
			->setMethod('DELETE');

		$router['ms.discount']->add('ms.discount.restore', '/{discountID}/restore/{hash}', '::Controller:Detail#restore')                                                                           
			->setRequirement('discountID', '\d+')
			->setMethod('GET')
			->enableCsrf('hash');

		$router['ms.discount']->add('ms.discount.edit.action', 'edit/{discountID}', '::Controller:Detail#processAttributes')
			->setRequirement('discountID', '\d+')
			->setMethod('POST');
		$router['ms.discount']->add('ms.discount.edit', 'edit/{discountID}', '::Controller:Detail#index')
			->setRequirement('discountID', '\d+');

		$router['ms.discount']->add('ms.discount.edit.criteria.action', 'edit/{discountID}/criteria', '::Controller:Detail#processCriteria')
			->setRequirement('discountID', '\d+')
			->setMethod('POST');
		$router['ms.discount']->add('ms.discount.edit.criteria', 'edit/{discountID}/criteria', '::Controller:Detail#criteria')
			->setRequirement('discountID', '\d+');

		$router['ms.discount']->add('ms.discount.edit.benefit.action', 'edit/{discountID}/benefit', '::Controller:Detail#processBenefit')
			->setRequirement('discountID', '\d+')
			->setMethod('POST');
		$router['ms.discount']->add('ms.discount.edit.benefit', 'edit/{discountID}/benefit', '::Controller:Detail#benefit')
			->setRequirement('discountID', '\d+');

		$router['ms.discount']->add('ms.discount.view.orders', 'view/{discountID}/orders', '::Controller:Detail#orders');

	}
}