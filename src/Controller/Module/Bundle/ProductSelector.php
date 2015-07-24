<?php

namespace Message\Mothership\Discount\Controller\Module\Bundle;

use Message\Mothership\Discount\Bundle;
use Message\Mothership\Discount\Form\BundleProductSelector\ProductSelectorGroupForm as SelectorForm;
use Message\Mothership\Commerce\Product\Product;
use Message\Mothership\Commerce\Order;
use Message\Cog\Controller\Controller;

class ProductSelector extends Controller
{
	private $_outOfStock = [];

	public function index(Bundle\Bundle $bundle)
	{
		return $this->render('Message:Mothership:Discount::bundle:product-selector', [
			'form'         => $this->_getForm($bundle),
			'form_fields'  => $this->_getFormFields($bundle),
			'unit_options' => $this->_getUnitOptionStrings($bundle),
			'bundle'       => $bundle,
		]);
	}

	public function addBundle($bundleID)
	{
		$bundle = $this->get('discount.bundle_loader')->getByID($bundleID);

		if (!$bundle) {
			throw new \LogicException('Cannot find bundle with ID `' . $bundleID . '`', 404);
		}

		$form = $this->_getForm($bundle);
		$form->handleRequest();

		if ($form->isValid()) {
			$data = $form->getData();

			$allItems = true;
			$units     = [];

			foreach ($data as $key => $value) {
				if (!array_key_exists('unit_id', $value)) {
					throw new \LogicException('Each row of data expects unit ID to be set in an array against a key of `unit_id`');
				}

				$unit = $this->get('product.unit.loader')->getByID($value['unit_id']);

				if (!$unit) {
					$allItems = false;
					break;
				}

				$units[] = $unit;
			}

			if ($allItems) {
				// Add bundle to order even if not all items were added, rely on validation to determine whether a
				// discount should be applied
				$bundleNotSet = true;
				$inc = 0;

				while ($bundleNotSet) {
					$metadataTag = 'bundle_' . $inc;
					if ($this->get('basket')->getOrder()->metadata->exists($metadataTag)) {
						++$inc;
					} else {
						$this->get('basket')->getOrder()->metadata->set($metadataTag, $bundleID);
						$bundleNotSet = false;
					}
				}

				foreach ($units as $unit) {
					$this->get('basket')->addUnit($unit);
				}
			} else {
				$this->addFlash('error', 'Could not add items to basket, they may be out of stock');
			}

//			$event = new Order\Event\Event($this->get('basket')->getOrder());
//			$this->get('event.dispatcher')->dispatch(
//				Bundle\Events::ADD_BUNDLE,
//				$event
//			);
		}

		return $this->redirectToReferer();
	}

	private function _getForm(Bundle\Bundle $bundle)
	{
		$products = $this->_getProducts($bundle);
		$units = [];

		foreach ($bundle->getProductRows() as $productRow) {
			$units[$productRow->getID()] = $this->_getUnits($products[$productRow->getProductID()], $productRow->getOptions());
		}

		$form = $this->createForm($this->get('discount.bundle.form.product_selector'), null, [
			'bundle'       => $bundle,
			'products'     => $products,
			'units'        => $units,
			'out_of_stock' => $this->_outOfStock,
			'action'       => $this->generateUrl('ms.product.basket.add_bundle', ['bundleID' => $bundle->getID()]),
		]);

		return $form;
	}

	private function _getUnitOptionStrings($bundle)
	{
		$unitOptions = [];

		foreach ($bundle->getProductRows() as $productRow) {
			for ($i = 0; $i < $productRow->getQuantity(); ++$i) {
				if (count($productRow->getOptions()) > 0) {
					$unitOptions[] = implode(', ', array_filter($productRow->getOptions()));
				} else {
					$unitOptions[] = null;
				}
			}
		}

		return $unitOptions;
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
				$outOfStock[] = $unit->id;
			}

			$units[$unit->id] = $unit;
		}

		$outOfStock = $outOfStock + $this->_outOfStock;

		$this->_outOfStock = array_unique($outOfStock);

		return $units;
	}
}