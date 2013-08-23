<?php

namespace Message\Mothership\Discount\Discount\Threshold;

use Message\Cog\DB\Query;
use Message\Cog\DB\Result;

class Loader
{
	protected $_query;

	public function __construct(Query $query)
	{
		$this->_query = $query;
	}

	public function getByDiscount($discount)
	{
		$result = $this->_query->run(
			'SELECT
				discount_id,
				locale,
				currency_id,
				threshold
			FROM
				discount_threshold
			WHERE
				discount_id  = ?i
		', 	array(
				$discount->id,
			)
		);

		$thresholds = $result->bindTo('Message\\Mothership\\Discount\\Discount\\Threshold\\Threshold');

		foreach ($result as $key => $data) {
			$thresholds[$key]->discount = $discount;
			$thresholds[$key]->threshold = (float) $data->threshold;

			// TODO Maybe load Locale-Object??
		}

		return $thresholds;
	}
}
