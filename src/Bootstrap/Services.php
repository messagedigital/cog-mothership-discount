<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Mothership\Discount;
use Message\Cog\Bootstrap\ServicesInterface;

class Services implements ServicesInterface
{
	public function registerServices($services)
	{
		$services['discount.loader'] = $services->factory(function($c) {
			return new Discount\Discount\Loader($c['db.query'], $c['product.loader']);
		});

		$services['discount.create'] = $services->factory(function($c) {
			return new Discount\Discount\Create($c['db.query'], $c['user.current']);
		});

		$services['discount.edit'] = $services->factory(function($c) {
			return new Discount\Discount\Edit($c['db.transaction'], $c['user.current']);
		});

		$services['discount.delete'] = $services->factory(function($c) {
			return new Discount\Discount\Delete($c['db.query'], $c['user.current']);
		});

		$services['discount.validator'] = $services->factory(function($c) {
			return new Discount\Discount\Validator($c['discount.loader'], $c['discount.order-discount-factory']);
		});

		$services['discount.order-discount-factory'] = $services->factory(function($c) {
			return new Discount\Discount\OrderDiscountFactory();
		});
	}
}