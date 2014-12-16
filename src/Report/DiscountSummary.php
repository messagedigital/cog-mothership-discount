<?php

namespace Message\Mothership\Discount\Report;

use Message\Cog\DB\QueryBuilderInterface;
use Message\Cog\DB\QueryBuilderFactory;
use Message\Cog\Routing\UrlGenerator;

use Message\Mothership\Report\Report\AbstractReport;
use Message\Mothership\Report\Chart\TableChart;

class DiscountSummary extends AbstractReport
{
	/**
	 * Constructor.
	 *
	 * @param QueryBuilderFactory   $builderFactory
	 * @param UrlGenerator          $routingGenerator
	 */
	public function __construct(QueryBuilderFactory $builderFactory, UrlGenerator $routingGenerator)
	{
		parent::__construct($builderFactory, $routingGenerator);
		$this->name = 'discount_summary';
		$this->displayName = 'Discount Summary';
		$this->reportGroup = 'Discounts & Vouchers';
		$this->_charts = [new TableChart];
	}

	/**
	 * Retrieves JSON representation of the data and columns.
	 * Applies data to chart types set on report.
	 *
	 * @return Array  Returns all types of chart set on report with appropriate data.
	 */
	public function getCharts()
	{
		$data = $this->_dataTransform($this->_getQuery()->run());
		$columns = $this->getColumns();

		foreach ($this->_charts as $chart) {
			$chart->setColumns($columns);
			$chart->setData($data);
		}

		return $this->_charts;
	}

	/**
	 * Set columns for use in reports.
	 *
	 * @return String  Returns columns in JSON format.
	 */
	public function getColumns()
	{
		$columns = [
			['type' => 'string', 'name' => "Code",           ],
			['type' => 'string', 'name' => "Details",        ],
			['type' => 'number', 'name' => "Created At",     ],
			['type' => 'number', 'name' => "Expires At",     ],
			['type' => 'string', 'name' => "Type",           ],
			['type' => 'string', 'name' => "Value",          ],
			['type' => 'boolean', 'name' => "Free Shipping", ],
			['type' => 'string', 'name' => "Currency",       ],
			['type' => 'number', 'name' => "Total Income",   ],
			['type' => 'number', 'name' => "Total Shipping", ],
			['type' => 'number', 'name' => "Total Discount Applied", ],
			['type' => 'number', 'name' => "Total Orders",   ],
			['type' => 'string', 'name' => "Status",         ],
		];

		return json_encode($columns);
	}

	/**
	 * Gets all discount data.
	 *
	 * @return Query
	 */
	private function _getQuery()
	{
		$queryBuilder = $this->_builderFactory->getQueryBuilder();

		$queryBuilder
			->select('discount.discount_id AS "ID"')
			->select('IFNULL(order_discount.code,"n/a") AS "Code"')
			->select('order_discount.name AS "Name"')
			->select('discount.start AS "Created"')
			->select('discount.end AS "Expires"')
			->select('IF(discount.percentage IS NULL, "Fixed", "Percentage") AS "Type"')
			->select('IF(discount.percentage IS NULL, CONCAT(d_amount.currency_id," ",d_amount.amount), CONCAT(discount.percentage,"%")) AS "Value"')
			->select('IF(free_shipping = 1,true,false) AS "FreeShipping"')
			->select('order_summary.currency_id AS "Currency"')
			->select('SUM(order_summary.product_gross) AS "TotalIncome"')
			->select('SUM(order_shipping.gross) AS "TotalShipping"')
			->select('SUM(order_discount.amount) AS "TotalDiscount"')
			->select('COUNT(order_summary.order_id) AS "TotalOrders"')
			->select('IF(discount.deleted_at > 0, "Deleted",IF(from_unixtime(discount.end) < NOW(), "Expired","Valid")) AS "Status"')
			->from('order_discount')
			->leftJoin('discount','order_discount.code = discount.code')
			->leftJoin('d_amount','d_amount.discount_id =  discount.discount_id','discount_amount')
			->leftJoin('order_summary','order_summary.order_id =  order_discount.order_id')
			->leftJoin('order_shipping','order_shipping.order_id =  order_discount.order_id')
			->groupBy('code, name, currency')
			->orderBY('discount.discount_id DESC')
		;

		return $queryBuilder->getQuery();
	}

	private function _dataTransform($data)
	{
		$result = [];

	/**
	 * Takes the data and transforms it into a useable format.
	 *
	 * @param  $data    DB\Result  The data from the report query.
	 * @param  $output  String     The type of output required.
	 *
	 * @return String|Array  Returns columns as string in JSON format or array.
	 */
		foreach ($data as $row) {

			$result[] = [
				$row->ID ?
					[
						'v' => $row->Code,
						'f' => (string) '<a href ="'.$this->generateUrl('ms.cp.discount.edit', ['discountID' => $row->ID]).'">'.$row->Code.'</a>'
					]
					: $row->Code,
				$row->Name,
				$row->Created ?
					[
						'v' => $row->Created,
						'f' => date('Y-m-d H:i', $row->Created)
					]
					: null,
				$row->Expires ?
					[
						'v' => $row->Expires,
						'f' => date('Y-m-d H:i', $row->Expires)
					]
					: null,
				$row->Type,
				$row->Value,
				(bool) $row->FreeShipping,
				$row->Currency,
				[
					'v' => (float) $row->TotalIncome,
					'f' => (string) number_format($row->TotalIncome,2,'.',',')
				],
				[
					'v' => (float) $row->TotalShipping,
					'f' => (string) number_format($row->TotalShipping,2,'.',',')
				],
				[
					'v' => (float) $row->TotalDiscount,
					'f' => (string) number_format($row->TotalDiscount,2,'.',',')
				],
				$row->TotalOrders,
				$row->Status,
			];

		}

		return json_encode($result);
	}
}