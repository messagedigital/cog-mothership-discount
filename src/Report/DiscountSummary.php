<?php

namespace Message\Mothership\Discount\Report;

use Message\Cog\DB\QueryBuilderInterface;
use Message\Report\ReportInterface;
use Message\Mothership\Report\Report\AbstractReport;
use Message\Cog\DB\QueryBuilderFactory;
use Message\Mothership\Report\Chart\TableChart;

class DiscountSummary extends AbstractReport
{
	private $_to = [];
	private $_from = [];
	private $_builderFactory;
	private $_charts;

	public function __construct(QueryBuilderFactory $builderFactory)
	{
		$this->name = "discount-summary-report";
		$this->_builderFactory = $builderFactory;
		$this->_charts = [new TableChart];
	}

	public function getName()
	{
		return $this->name;
	}

	public function getCharts()
	{
		$data = $this->dataTransform($this->getQuery()->run());

		foreach ($this->_charts as $chart) {
			$chart->setData($data);
		}

		return $this->_charts;
	}

	public function getQuery()
	{
		$queryBuilder = $this->_builderFactory->getQueryBuilder();

		$queryBuilder
			->select('discount.discount_id AS "ID"')
			->select('discount.code AS "Code"')
			->select('discount.name AS "Name"')
			->select('DATE_FORMAT(from_unixtime(discount.start),"%d %b %Y %h:%i") AS "Created"')
			->select('DATE_FORMAT(from_unixtime(discount.end),"%d %b %Y %h:%i") AS "Expires"')
			->select('IFNULL(IF(discount.percentage IS NULL, CONCAT("£",d_amount.amount), CONCAT(discount.percentage,"%")),"") AS "Value"')
			->select('IFNULL(discount_used.amount,"") AS "Total Discount"')
			->select('IF(free_shipping = 1,"Yes","No") AS "Free Shipping"')
			->select('IF(discount.deleted_at > 0, "Deleted",IF(from_unixtime(discount.end) < NOW(), "Expired","Valid")) AS "Status"')
			->from('discount')
			->leftJoin('d_amount','d_amount.discount_id =  discount.discount_id','discount_amount')
			->leftJoin('discount_used','discount_used.code = discount.code',
				$this->_builderFactory->getQueryBuilder()
					->select('order_id')
					->select('code')
					->select('SUM(amount) AS amount')
					->from('order_discount')
					->groupBy('code')
				)
			->groupBy('discount.code')
			->orderBY('discount.discount_id ASC')
		;


		return $queryBuilder->getQuery();
	}

	protected function dataTransform($data)
	{
		$result = [];
		$result[] = $data->columns();

		foreach ($data as $row) {
			$result[] = get_object_vars($row);

		}

		return $result;
	}
}
