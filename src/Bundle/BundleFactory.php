<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Discount\Form;
use Message\Mothership\FileManager\File;
use Message\Mothership\Commerce\Product\Loader as BaseProductLoader;

class BundleFactory
{
	/**
	 * @var BaseProductLoader
	 */
	private $_productLoader;

	private $_fileLoader;

	private $_currencies;

	private $_requiredFields = [
		Form\BundleForm::NAME,
		Form\BundleForm::PRODUCT,
	];

	private $_requiredProductFields = [
		Form\BundleProductForm::PRODUCT,
		Form\BundleProductForm::QUANTITY,
	];

	public function __construct(BaseProductLoader $productLoader, File\FileLoader $fileLoader, array $currencies)
	{
		$this->_productLoader = $productLoader;
		$this->_fileLoader = $fileLoader;
		$this->_currencies = $currencies;
		$this->_buildRequiredFields($currencies);
	}

	public function build(array $data)
	{
		$this->_validateData($data);

		$bundle = new Bundle;

		if (!empty($data[Form\BundleForm::ID])) {
			$bundle->setID($data[Form\BundleForm::ID]);
		}

		$bundle->setName($data[Form\BundleForm::NAME]);

		if (!empty($data[Form\BundleForm::START])) {
			$bundle->setStart($data[Form\BundleForm::START]);
		}

		if (!empty($data[Form\BundleForm::END])) {
			$bundle->setEnd($data[Form\BundleForm::END]);
		}

		$bundle->setAllowCodes(!empty($data[Form\BundleForm::CODES]));

		$this->_addProducts($bundle, $data);
		$this->_addPrices($bundle, $data);
		$this->_addImage($bundle, $data);

		return $bundle;
	}

	private function _addProducts(Bundle $bundle, array $data)
	{
		foreach ($data[Form\BundleForm::PRODUCT] as $product) {
			$this->_validateProductData($product);


			$options = (!empty($product[Form\BundleProductForm::OPTION_NAME]) && !empty($product[Form\BundleProductForm::OPTION_VALUE])) ?
				[$product[Form\BundleProductForm::OPTION_NAME] => $product[Form\BundleProductForm::OPTION_VALUE]] :
				[];

			$row = new ProductRow(
				$product[Form\BundleProductForm::PRODUCT],
				$options,
				$product[Form\BundleProductForm::QUANTITY]
			);

			$bundle->addProductRow($row);
		}
	}

	private function _addPrices(Bundle $bundle, array $data)
	{
		foreach ($this->_currencies as $currency) {
			$bundle->setPrice($data[Form\BundleForm::PRICE_PREFIX . strtoupper($currency)], $currency);
		}
	}

	private function _addImage(Bundle $bundle, array $data)
	{
		if (!empty($data[Form\BundleForm::IMAGE])) {
			$id = $data[Form\BundleForm::IMAGE];
			$image = $this->_fileLoader->getByID($id);

			if (!$image) {
				throw new Exception\BundleBuildException('Could not load file with ID `' . $id . '`');
			}

			if (!$image instanceof File\File || $image->typeID !== File\Type::IMAGE) {
				throw new Exception\BundleBuildException('File with ID `' . $id . '` is not a valid image');
			}

			$bundle->setImage($image);
		}
	}

	private function _validateProductData(array $data)
	{
		foreach ($this->_requiredProductFields as $required) {
			if (!array_key_exists($required, $data)) {
				throw new Exception\BundleBuildException('Product data is missing `' . $required . '` field');
			}
		}

		if (!empty($data[Form\BundleProductForm::OPTION_NAME]) xor !empty($data[Form\BundleProductForm::OPTION_VALUE])) {
			throw new Exception\BundleBuildException(
				'Product (' .
				$data[Form\BundleProductForm::PRODUCT] .
				') data must contain either both option name and value, or neither'
			);
		}

		if ($data[Form\BundleProductForm::OPTION_NAME] && $data[Form\BundleProductForm::OPTION_VALUE]) {
			$product = $this->_productLoader->getByID($data[Form\BundleProductForm::PRODUCT]);
			$optionName = $data[Form\BundleProductForm::OPTION_NAME];
			$optionValue = $data[Form\BundleProductForm::OPTION_VALUE];

			$optionExists = false;

			foreach ($product->getUnits() as $unit) {
				if ($unit->hasOption($optionName) && $unit->getOption($optionName) === $optionValue) {
					$optionExists = true;
					break;
				}
			}

			if (false === $optionExists) {
				throw new Exception\BundleBuildException(
					'Product `' .
					$product->name .
					'` does not have any units with an option name of `' .
					$optionName . '` and value of ' . $optionValue . '`'
				);
			}

		}
	}

	private function _validateData(array $data)
	{
		foreach ($this->_requiredFields as $required) {
			if (!array_key_exists($required, $data)) {
				throw new Exception\BundleBuildException('Data is missing `' . $required . '` field');
			}
		}

		if (!is_array($data[Form\BundleForm::PRODUCT])) {
			throw new Exception\BundleBuildException('Product data must be an array');
		}

		if (count($data[Form\BundleForm::PRODUCT]) <= 0) {
			throw new Exception\BundleBuildException('Bundles must have at least one product');
		}
	}

	private function _buildRequiredFields()
	{
		foreach ($this->_currencies as $currency) {
			if (!is_string($currency)) {
				throw new \LogicException('Currency array contains values that are not strings');
			}

			$this->_requiredFields[] = Form\BundleForm::PRICE_PREFIX . strtoupper($currency);
		}
	}
}