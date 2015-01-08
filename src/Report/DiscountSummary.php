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
		$this->_setName('discount_summary');
		$this->_setDisplayName('Discount Summary');
		$this->_setReportGroup('Discounts & Vouchers');
		$this->_charts = [new TableChart];
	}

	/**
	 * Retrieves JSON representation of the data and columns.
	 * Applies data to chart types set on report.
	 *
	 * @return array  Returns all types of chart set on report with appropriate data.
	 */
	public function getCharts()
	{
		$data = $this->_dataTransform($this->_getQuery()->run(), "json");
		$columns = $this->_parseColumns($this->getColumns());

		foreach ($this->_charts as $chart) {
			$chart->setColumns($columns);
			$chart->setData($data);
		}

		return $this->_charts;
	}

	/**
	 * Set columns for use in reports.
	 *
	 * @return array  Returns array of columns as keys with format for Google Charts as the value.
	 */
	public function getColumns()
	{
		return [
			'Code'                   => 'string',
			'Details'                => 'string',
			'Created At'             => 'number',
			'Expires At'             => 'number',
			'Type'                   => 'string',
			'Value'                  => 'string',
			'Free Shipping'          => 'boolean',
			'Currency'               => 'string',
			'Total Income'           => 'number',
			'Total Shipping'         => 'number',
			'Total Discount Applied' => 'number',
			'Total Orders'           => 'number',
			'Status'                 => 'string',
		];

		return json_encode($columns);
	}

	/**
	 * Gets all discount data.
	 *
	 * @return Query
	 */
	protected function _getQuery()
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

	/**
	 * Takes the data and transforms it into a useable format.
	 *
	 * @param  $data    DB\Result  The data from the report query.
	 * @param  $output  string     The type of output required.
	 *
	 * @return string|array  Returns data as string in JSON format or array.
	 */
	protected function _dataTransform($data, $output = null)
	{
		$result = [];

		if ($output === "json") {

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
							'v' => (int) $row->Created,
							'f' => date('Y-m-d H:i', $row->Created)
						]
						: null,
					$row->Expires ?
						[
							'v' => (int) $row->Expires,
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
					(int) $row->TotalOrders,
					$row->Status,
				];

			}
			return json_encode($result);

		} else {

			foreach ($data as $row) {
				$result[] = [
					$row->Code,
					$row->Name,
					date('Y-m-d H:i', $row->Created),
					date('Y-m-d H:i', $row->Expires),
					$row->Type,
					$row->Value,
					$row->FreeShipping ? 'Yes' : 'No',
					$row->Currency,
					$row->TotalIncome,
					$row->TotalShipping,
					$row->TotalDiscount,
				];
			}
			return $result;
		}
	}
}