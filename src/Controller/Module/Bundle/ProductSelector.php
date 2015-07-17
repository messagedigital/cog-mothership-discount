<?php

namespace Message\Mothership\Discount\Controller\Module\Bundle;

use Message\Mothership\Discount\Bundle;
use Message\Mothership\Discount\Form\BundleProductSelector\ProductSelectorGroupForm as SelectorForm;
use Message\Mothership\Commerce\Product\Product;
use Message\Cog\Controller\Controller;

class ProductSelector extends Controller
{
	private $_units;
	private $_outOfStock;

	public function index(Bundle\Bundle $bundle)
	{
		$products = $this->_getProducts($bundle);
		$units = [];
		$outOfStock = [];

		foreach ($bundle->getProductRows() as $productRow) {
			$units[$productRow->getID()] = $this->_getUnits($products[$productRow->getProductID()], $productRow->getOptions());
		}

		$form = $this->createForm($this->get('discount.bundle.form.product_selector'), null, [
			'bundle'       => $bundle,
			'products'     => $products,
			'units'        => $units,
			'out_of_stock' => $outOfStock,
		]);

		$formFields = $this->_getFormFields($bundle);

		return $this->render('Message:Mothership:Discount::bundle:product-selector', [
			'form' => $form,
			'bundle' => $bundle,
			'form_fields' => $formFields,
		]);
	}

	private function _getFormFields(Bundle\Bundle $bundle)
	{
		$formFields = [];

		foreach ($bundle->getProductRows() as $productRow) {
			for ($i = 0; $i < $productRow->getQuantity(); ++$i) {
				$formFields[] = SelectorForm::PRODUCT_ROW . $productRow->getID() . '_' . $i;
			}
		}

		return $formFields;
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

		$outOfStock = $outOfStock + $this->_outOfStock;

		$this->_outOfStock = array_unique($outOfStock);

		return $units;
	}
}