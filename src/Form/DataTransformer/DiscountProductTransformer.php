<?php

namespace Message\Mothership\Discount\Form\DataTransformer;

use Message\Mothership\Commerce\Product\Loader;
use Symfony\Component\Form\DataTransformerInterface;

class DiscountProductTransformer implements DataTransformerInterface
{
	/**
	 * @var \Message\Mothership\Commerce\Product\Loader
	 */
	protected $_productLoader;

	public function __construct(Loader $productLoader)
	{
		$this->_productLoader = $productLoader;
	}

	public function transform($products)
	{
		$productArray = [];

		foreach ($products as $product) {
			$productArray[$product->id] = $product->id;
		}

		return $productArray;
	}

	public function reverseTransform($products)
	{
		$products = (array) $products;

		return $this->_productLoader->getByID($products);
	}
}