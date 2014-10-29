<?php

namespace Message\Mothership\Discount\Report;

use Message\Cog\DB\QueryBuilderInterface;
use Message\Cog\DB\QueryBuilderFactory;
use Message\Cog\Localisation\Translator;

use Message\Mothership\Report\Report\AbstractReport;
use Message\Mothership\Report\Chart\TableChart;

use Message\Report\ReportInterface;

class DiscountSummary extends AbstractReport
{
	private $_builderFactory;
	private $_charts;

	public function __construct(QueryBuilderFactory $builderFactory, Translator $trans)
	{
		$this->name = 'discount_summary';
		$this->reportGroup = 'Discounts & Vouchers';
		$this->_builderFactory = $builderFactory;
		$this->_charts = [new TableChart];
	}

	public function getName()
	{
		return $this->name;
	}

	public function getReportGroup()
	{
		return $this->reportGroup;
	}

	public function getCharts()
	{
		$data = $this->dataTransform($this->getQuery()->run());
		$columns = $this->getColumns();

		foreach ($this->_charts as $chart) {
			$chart->setColumns($columns);
			$chart->setData($data);
		}

		return $this->_charts;
	}

	public function getColumns()
	{
		$columns = [
			['type' => 'number', 	'name' => "ID",			],
			['type' => 'string',	'name' => "Code",		],
			['type' => 'string',	'name' => "Details",	],
			['type' => 'number',	'name' => "Created",	],
			['type' => 'number',	'name' => "Expires",	],
			['type' => 'string',	'name' => "Type",		],
			['type' => 'string',	'name' => "Value",		],
			['type' => 'number',	'name' => "Total Discount",	],
			['type' => 'boolean',	'name' => "Free Shipping",	],
			['type' => 'string',	'name' => "Status",		],
		];

		return json_encode($columns);
	}

	public function getQuery()
	{
		$queryBuilder = $this->_builderFactory->getQueryBuilder();

		$queryBuilder
			->select('discount.discount_id AS "ID"')
			->select('IFNULL(order_discount.code,"n/a") AS "Code"')
			->select('order_discount.name AS "Name"')
			->select('discount.start AS "Created"')
			->select('discount.end AS "Expires"')
			->select('IF(discount.percentage IS NULL, "fixed value", "percentage") AS "Type"')
			->select('IF(discount.percentage IS NULL, CONCAT(d_amount.currency_id," ",d_amount.amount), CONCAT(discount.percentage,"%")) AS "Value"')
			->select('SUM(order_discount.amount) AS "TotalDiscount"')
			->select('IF(free_shipping = 1,true,false) AS "FreeShipping"')
			->select('IF(discount.deleted_at > 0, "Deleted",IF(from_unixtime(discount.end) < NOW(), "Expired","Valid")) AS "Status"')
			->from('order_discount')
			->leftJoin('discount','order_discount.code = discount.code')
			->leftJoin('d_amount','d_amount.discount_id =  discount.discount_id','discount_amount')
			->groupBy('code, name')
			->orderBY('discount.discount_id ASC')
		;

		return $queryBuilder->getQuery();
	}

	protected function dataTransform($data)
	{
		$result = [];

		foreach ($data as $row) {

			$created = $row->Created ? [ 'v' => $row->Created, 'f' => date('Y-m-d H:i', $row->Created)] : null;
			$expires = $row->Expires ? [ 'v' => $row->Expires, 'f' => date('Y-m-d H:i', $row->Expires)] : null;

			$result[] = [
				$row->ID,
				$row->Code,
				$row->Name,
				$created,
				$expires,
				$row->Type,
				$row->Value,
				[ 'v' => (float) $row->TotalDiscount, 'f' => $row->TotalDiscount],
				(bool) $row->FreeShipping,
				$row->Status,
			];

		}

		return json_encode($result);
	}
}