<?php

namespace Message\Mothership\Discount\Controller\Module\Dashboard;

use Message\Cog\Controller\Controller;

/**
 * Discount revenue dashboard module.
 *
 * @author Laurence Roberts <laurence@message.co.uk>
 */
class DiscountRevenue extends Controller
{
	/**
	 * Get the total gross and customer savings on discounted orders for the
	 * past 7 days.
	 *
	 * @return Message\Cog\HTTP\Response
	 */
	public function index()
	{
		$grossDataset = $this->get('statistics')->get('discounted.sales.gross');
		$discountDataset = $this->get('statistics')->get('discount.gross');

		$gross = $grossDataset->range->getTotal($grossDataset->range->getWeekAgo());
		$discount = $discountDataset->range->getTotal($grossDataset->range->getWeekAgo());

		$rows = [];

		$rows[] = [
			'label' => 'Income from discounted orders',
			'value' => $gross
		];

		$rows[] = [
			'label' => 'Customer savings',
			'value' => $discount
		];

		return $this->render('Message:Mothership:ControlPanel::module:dashboard:column-graph', [
			'label' => 'Discounted Revenue (week)',
			'keys' => [
				'label' => 'Value',
				'value' => 'Amount',
			],
			'rows' => $rows,
			'filterPrice' =>true,
			'currency' => $this->get('currency.company'),
		]);
	}
}