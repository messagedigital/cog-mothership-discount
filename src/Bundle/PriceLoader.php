<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\QueryBuilderFactory;

class PriceLoader
{
	private $_queryBuilderFactory;

	public function __construct(QueryBuilderFactory $queryBuilderFactory)
	{
		$this->_queryBuilderFactory = $queryBuilderFactory;
	}
}