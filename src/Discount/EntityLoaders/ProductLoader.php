<?php

namespace Message\Mothership\Discount\Discount\EntityLoaders; 

use Message\Mothership\Discount\Discount\Discount;
use Message\Mothership\Commerce\Product\Loader as BaseProductLoader;
use Message\Mothership\Commerce\Product\Collection as ProductCollection;
use Message\Cog\DB\Query;

class ProductLoader implements DiscountEntityLoaderInterface 
{
	private $_productLoader;
	private $_query;

	public function __construct(Query $query, BaseProductLoader $productLoader)
	{
		$this->_query         = $query;
		$this->_productLoader = $productLoader;
	}

	public function getByDiscount(Discount $discount)
	{
		$idQuery = $this->_query->run(
				'SELECT
					product_id	AS productID
				FROM
					discount_product
				WHERE
					discount_id = ?i', 
				[$discount->id]
			);

		$products = $this->_productLoader->getByID($idQuery->flatten());

		return new ProductCollection(is_array($products)?$products:[$products]);
	}
}