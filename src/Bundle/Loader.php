<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\QueryBuilderFactory;

class Loader
{
	private $_queryBuilderFactory;
	private $_priceLoader;

	public function __construct(QueryBuilderFactory $queryBuilderFactory, PriceLoader $priceLoader)
	{
		$this->_queryBuilderFactory = $queryBuilderFactory;
		$this->_priceLoader         = $priceLoader;
	}
}