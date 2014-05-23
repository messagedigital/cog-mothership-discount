<?php

namespace Message\Mothership\Discount\Form\DataTransformer;

use Message\Mothership\Commerce\Product\Loader;
use Symfony\Component\Form\DataTransformerInterface;

class DiscountProductTransformer implements DataTransformerInterface
{
	/**
	 * @var \Message\Mothership\Commerce\Product\Loader
	 */
	protected $_loader;

	public function __construct(Loader $loader)
	{
		$this->_loader = $loader;
	}

	public function transform($products)
	{
		$products = (array) $products;

		foreach ($products as $key => $product) {
			$products[$key] = $product->id;
		}

		return $products;
	}

	public function reverseTransform($products)
	{
		$products = (array) $products;

		return $this->_loader->getByID($products);
	}
}