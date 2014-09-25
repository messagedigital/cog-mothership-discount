<?php

namespace Message\Mothership\Discount\Discount;

use Message\Mothership\Commerce\Product\Product;
use Message\Cog\DB\Entity\EntityLoaderCollection;
use Message\Mothership\Commerce\Product\Collection as ProductCollection;
use Message\Mothership\Commerce\Product\Product as ProductLoader;

class DiscountProxy extends Discount
{
	private $_entityLoaders;
	private $_loaded;

	public function __construct(EntityLoaderCollection $entityLoaders)
	{
		parent::__construct();

		$this->_loaded        = [];
		$this->_entityLoaders = $entityLoaders;
	}

	/**
     * @{inheritdoc}
     */
    public function getProducts()
    {
    	if (!in_array('product', $this->_loaded)) {
	    	$this->_products = $this->_entityLoaders->get('product')->getByDiscount($this);
	    	$_loaded[] = 'product';
    	}

        return parent::getProducts();
    }
}