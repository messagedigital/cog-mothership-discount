<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Mothership\Discount;
use Message\Cog\Bootstrap\ServicesInterface;
use Message\Mothership\Report\Report\Collection as ReportCollection;

class Services implements ServicesInterface
{
	public function registerServices($services)
	{
		$this->registerStatisticsDatasets($services);
		$this->registerReports($services);

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
			return new Discount\Form\DiscountCriteriaForm($c['db.query'], $c['product.loader']);
		});

		$services['discount.validator'] = function($c) {
			return new Discount\Discount\Validator(
				$c['discount.loader'],
				$c['discount.order-discount-factory'],
				$c['db.query'],
				$c['translator']
			);
		};

		$services['discount.order-discount-factory'] = $services->factory(function($c) {
			return new Discount\Discount\OrderDiscountFactory();
		});

		// Bundles
		$services['discount.bundle_create'] = function ($c) {
			return new Discount\Bundle\Create(
				$c['db.query'],
				$c['discount.bundle.product_create'],
				$c['discount.bundle.price_create'],
				$c['discount.bundle.image_create'],
				$c['user.current']
			);
		};

		$services['discount.bundle_edit'] = function ($c) {
			return new Discount\Bundle\Edit(
				$c['db.transaction'],
				$c['discount.bundle.product_create'],
				$c['discount.bundle.price_create'],
				$c['discount.bundle.image_create'],
				$c['user.current']
			);
		};

		// Not a singleton as query can be replaced with transaction
		$services['discount.bundle.product_create'] = $services->factory(function ($c) {
			return new Discount\Bundle\BundleProductCreate(
				$c['db.query'],
				$c['db.query.parser']
			);
		});

		// Not a singleton as query can be replaced with transaction
		$services['discount.bundle.price_create'] = $services->factory(function ($c) {
			return new Discount\Bundle\BundlePriceCreate(
				$c['db.query'],
				$c['db.query.parser']
			);
		});

		// Not a singleton as query can be replaced with transaction
		$services['discount.bundle.image_create'] = $services->factory(function ($c) {
			return new Discount\Bundle\BundleImageCreate($c['db.query']);
		});

		$services['discount.bundle_factory'] = function($c) {
			return new Discount\Bundle\BundleFactory(
				$c['product.loader'],
				$c['file_manager.file.loader'],
				$c['cfg']->currency->supportedCurrencies
			);
		};

		$services['discount.bundle.form.bundle'] = function ($c) {
			return new Discount\Form\BundleForm(
				$c['file_manager.file.loader'],
				$c['translator'],
				$c['discount.bundle_factory'],
				$c['discount.bundle.form.bundle_product'],
				$c['cfg']->currency->supportedCurrencies
			);
		};

		$services['discount.bundle.form.bundle_product'] = function ($c) {
			return new Discount\Form\BundleProductForm(
				$c['product.loader'],
				$c['product.option.loader']
			);
		};

		$services['discount.bundle.form.product_selector'] = function($c) {
			return new Discount\Form\BundleProductSelector\ProductSelectorGroupForm;
		};

		$services['discount.bundle_loader'] = function($c) {
			return new Discount\Bundle\Loader(
				$c['db.query.builder.factory'],
				$c['user.loader'],
				$c['discount.bundle.entity_collection']
			);
		};

		$services['discount.bundle.entity_collection'] = function ($c) {
			return new \Message\Cog\DB\Entity\EntityLoaderCollection([
				'file'        => $c['discount.bundle.file_loader'],
				'product_row' => $c['discount.bundle.product_row_loader'],
				'price'       => $c['discount.bundle.price_loader']
			]);
		};

		$services['discount.bundle.file_loader'] = function ($c) {
			return new Discount\Bundle\FileLoader(
				$c['file_manager.file.loader']
			);
		};

		$services['discount.bundle.product_row_loader'] = function ($c) {
			return new Discount\Bundle\ProductRowLoader(
				$c['db.query.builder.factory']
			);
		};

		$services['discount.bundle.price_loader'] = function ($c) {
			return new Discount\Bundle\PriceLoader(
				$c['db.query.builder.factory']
			);
		};

		// Extended services
		$services->extend('field.collection', function($fields, $c) {
			$fields->add(new Discount\Field\Bundle($c['discount.bundle_loader']));

			return $fields;
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

	public function registerReports($services)
	{
		$services['discount.discount_summary'] = $services->factory(function($c) {
			return new Discount\Report\DiscountSummary(
				$c['db.query.builder.factory'],
				$c['routing.generator']
			);
		});

		$services['discount.reports'] = function($c) {
			$reports = new ReportCollection;
			$reports
				->add($c['discount.discount_summary'])
			;

			return $reports;
		};
	}
}