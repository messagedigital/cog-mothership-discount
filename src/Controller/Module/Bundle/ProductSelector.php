<?php

namespace Message\Mothership\Discount\Controller\Module\Bundle;

use Message\Mothership\Discount\Bundle;
use Message\Mothership\Commerce\Product\Product;
use Message\Cog\Controller\Controller;

class ProductSelector extends Controller
{

	public function index(Bundle\Bundle $bundle)
	{
		$products = $this->_getProducts($bundle);
		$units = [];
		$outOfStock = [];

		foreach ($bundle->getProductRows() as $productRow) {
			list($units[$productRow->getID()], $outOfStock[$productRow->getID()]) =
				$this->_getUnits($products[$productRow->getProductID()], $productRow->getOptions());
		}

	}

	private function _getProducts(Bundle\Bundle $bundle)
	{
		$productIDs = [];
		$products   = [];

		foreach ($bundle->getProductRows() as $productRow) {
			$productIDs[] = $productRow->getProductID();
		}

		foreach ($this->get('product.loader')->getByID($productIDs) as $product) {
			$products[$product->id] = $product;
		}

		return $products;
	}

	private function _getUnits(Product $product, array $options)
	{
		$units = [];
		$outOfStock = [];

		$locations = $this->get('stock.locations');

		foreach ($product->getVisibleUnits() as $unit) {
			// Skip units that don't meet the options criteria, if set
			if ($options && $options !== array_intersect_assoc($options, $unit->options)) {
				continue;
			}

			if (1 > $unit->getStockForLocation($locations->getRoleLocation($locations::SELL_ROLE))) {
				$outOfStock[] = $unit;
			}

			$units[$unit->id] = $unit;
		}

		return [$units, $outOfStock];
	}
}