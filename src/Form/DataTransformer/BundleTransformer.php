<?php

namespace Message\Mothership\Discount\Form\DataTransformer;

use Message\Mothership\Discount\Form\BundleForm;
use Message\Mothership\Discount\Form\BundleProductForm;
use Message\Mothership\Discount\Bundle;
use Symfony\Component\Form\DataTransformerInterface;

class BundleTransformer implements DataTransformerInterface
{
	private $_factory;

	public function __construct(Bundle\BundleFactory $factory)
	{
		$this->_factory = $factory;
	}

	public function transform($bundle)
	{
		if (null === $bundle) {
			return null;
		}

		if (!$bundle instanceof Bundle\Bundle) {
			throw new \InvalidArgumentException('Form data must be created from a bundle');
		}

		$data = [
			BundleForm::ID    => $bundle->getID(),
			BundleForm::NAME  => $bundle->getName(),
			BundleForm::START => $bundle->getStart(),
			BundleForm::END   => $bundle->getEnd(),
			BundleForm::IMAGE => $bundle->getImage()->id,
			BundleForm::CODES => $bundle->allowCodes(),
			BundleForm::PRODUCT => [],
		];

		foreach ($bundle->getPrices() as $currency => $price) {
			$data[BundleForm::PRICE_PREFIX . strtoupper($currency)] = $price;
		}

		foreach ($bundle->getProductRows() as $productRow) {
			$productData = [
				BundleProductForm::PRODUCT => $productRow->getProductID(),
				BundleProductForm::QUANTITY => $productRow->getQuantity(),
			];

			foreach ($productRow->getOptions() as $name => $value) {
				$productData[BundleProductForm::OPTION_NAME] = $name;
				$productData[BundleProductForm::OPTION_VALUE] = $value;
			}

			$data[BundleForm::PRODUCT][] = $productData;
		}

		return $data;
	}

	public function reverseTransform($data)
	{
		try {
			return $this->_factory->build($data);
		} catch (Bundle\Exception\BundleBuildException $e) {
			return $data;
		}
	}
}