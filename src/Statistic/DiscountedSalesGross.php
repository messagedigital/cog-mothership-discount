<?php

namespace Message\Mothership\Discount\Statistic;

use Message\Mothership\ControlPanel\Statistic\AbstractDataset;

class DiscountedSalesGross extends AbstractDataset
{
	public function getName()
	{
		return 'discounted.sales.gross';
	}

	public function getPeriodLength()
	{
		return static::DAILY;
	}

	public function rebuild()
	{
		$this->_query->run("
			DELETE FROM
				statistic
			WHERE
				dataset = 'discounted.sales.gross';
		");

		$this->_query->run("
			INSERT INTO
				statistic
			SELECT
				'discounted.sales.gross',
				'discounted.sales.gross',
				created_at - MOD(created_at, 60 * 60 * 24) as day_start,
				SUM(total_gross),
				UNIX_TIMESTAMP(NOW())
			FROM
				order_summary
			WHERE
				total_discount > 0
			GROUP BY
				day_start;
		");

		if (! $this->_transOverriden) {
			$this->_query->commit();
		}
	}
}