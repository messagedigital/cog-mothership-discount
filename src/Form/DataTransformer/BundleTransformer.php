<?php

namespace Message\Mothership\Discount\Form\DataTransformer;

use Message\Mothership\Discount\Form\BundleForm;
use Message\Mothership\Discount\Form\BundleProductForm;
use Message\Mothership\Discount\Bundle;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class BundleTransformer
 * @package Message\Mothership\Discount\Form\DataTransformer
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 */
class BundleTransformer implements DataTransformerInterface
{
	/**
	 * @var Bundle\BundleFactory
	 */
	private $_factory;

	/**
	 * @param Bundle\BundleFactory $factory
	 */
	public function __construct(Bundle\BundleFactory $factory)
	{
		$this->_factory = $factory;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array | null
	 */
	public function transform($bundle)
	{
		if (null === $bundle) {
			return null;
		}

		if (is_array($bundle)) {
			return $bundle;
		}

		if (!$bundle instanceof Bundle\Bundle) {
			throw new \InvalidArgumentException('Form data must be created from a bundle');
		}

		$data = [
			BundleForm::ID      => $bundle->getID(),
			BundleForm::NAME    => $bundle->getName(),
			BundleForm::START   => $bundle->getStart(),
			BundleForm::END     => $bundle->getEnd(),
			BundleForm::CODES   => $bundle->allowCodes(),
			BundleForm::PRODUCT => [],
		];

		if ($bundle->getImage()) {
			$data[BundleForm::IMAGE] = $bundle->getImage()->id;
		}

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

	/**
	 * If a bundle cannot be build from the form data, the data will be returned as normal but with a new value 'error'
	 * for the error message.
	 *
	 * {@inheritDoc}
	 *
	 * @return Bundle\Bundle | array
	 */
	public function reverseTransform($data)
	{
		try {
			return $this->_factory->build($data);
		} catch (Bundle\Exception\BundleBuildException $e) {
			$data['error'] = $e->getMessage();

			return $data;
		}
	}
}