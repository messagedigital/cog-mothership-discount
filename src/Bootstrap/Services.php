<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Mothership\Discount;
use Message\Cog\Bootstrap\ServicesInterface;

class Services implements ServicesInterface
{
	public function registerServices($services)
	{
		$this->registerStatisticsDatasets($services);

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

		$services['discount.form.create'] = $services->factory(function($c) {
			return new Discount\Form\DiscountCreateForm($c['cfg']->discount->maxCodeLength);
		});

		$services['discount.form.attributes'] = $services->factory(function($c) {
			return new Discount\Form\DiscountAttributesForm($c['cfg']->discount->maxCodeLength);
		});

		$services['discount.form.benefit'] = $services->factory(function($c) {
			return new Discount\Form\DiscountBenefitForm;
		});

		$services['discount.form.criteria'] = $services->factory(function($c) {
			return new Discount\Form\DiscountCriteriaForm($c['product.loader']->getAll());
		});

		$services['discount.validator'] = $services->factory(function($c) {
			return new Discount\Discount\Validator($c['discount.loader'], $c['discount.order-discount-factory'], $c['db.query']);
		});

		$services['discount.order-discount-factory'] = $services->factory(function($c) {
			return new Discount\Discount\OrderDiscountFactory();
		});
	}

	public function registerStatisticsDatasets($services)
	{
		$services->extend('statistics', function($statistics, $c) {
			$statistics->add(new Discount\Statistic\DiscountGross($c['db.query'], $c['statistics.counter.key'], $c['statistics.range.date']));
			$statistics->add(new Discount\Statistic\DiscountedSalesGross($c['db.query'], $c['statistics.counter.key'], $c['statistics.range.date']));

			return $statistics;
		});
	}
}