<?php

namespace Message\Mothership\Discount\Statistic;

use Message\Mothership\ControlPanel\Statistic\AbstractDataset;

class DiscountGross extends AbstractDataset
{
	public function getName()
	{
		return 'discount.gross';
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
				dataset = 'discount.gross';
		");

		$this->_query->run("
			INSERT INTO
				statistic
			SELECT
				'discount.gross',
				'discount.gross',
				created_at - MOD(created_at, 60 * 60 * 24) as day_start,
				SUM(total_discount),
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