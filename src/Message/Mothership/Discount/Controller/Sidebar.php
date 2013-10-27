<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;

class Sidebar extends Controller
{
	public function index()
	{
		return $this->render('Message:Mothership:Discount::sidebar', array(
			'code_search_form' => $this->_getCodeSearchForm(),
			'date_search_form' => $this->_getDateSearchForm(),
		));
	}

	public function searchCodeAction()
	{
		$form = $this->_getCodeSearchForm();
		if ($form->isValid() && $data = $form->getFilteredData()) {
			$discountCode = $data['term'];

			$discount = $this->get('discount.loader')->getByCode($discountCode);

			if ($discount) {
				return $this->redirectToRoute('ms.discount.edit', array('discountID' => $discount->id));
			} else {
				$this->addFlash('warning', sprintf('No search results were found for "%s"', $discountCode));
				return $this->redirectToReferer();
			}
		}
	}

	public function searchDateAction()
	{
		$form = $this->_getDateSearchForm();
		if ($form->isValid() && $data = $form->getFilteredData()) {
			try {
				$from = new \Datetime($data['from']);
				$to   = new \Datetime($data['to']);
			} catch(\Exception $e) {
				$this->addFlash('error', 'Invalid input for dates!');
				return $this->redirectToReferer();
			}

			return $this->redirectToRoute('ms.discount.listing.active.date',
				array(
					'fromTimestamp' => $from->getTimestamp(),
					'toTimestamp' => $to->getTimestamp(),
				)
			);
		}
	}

	protected function _getCodeSearchForm()
	{
		$form = $this->get('form')
			->setName('code_search')
			->setMethod('POST')
			->setAction($this->generateUrl('ms.discount.sidebar.search.code.action'));
		$form->add('term', 'search', 'Search');

		return $form;
	}

	protected function _getDateSearchForm()
	{
		$form = $this->get('form')
			->setName('date_search')
			->setMethod('GET')
			->setAction($this->generateUrl('ms.discount.sidebar.search.date.action'));

		$form->add('from', 'text', 'From', array('attr' => array('placeholder' => 'From...')));
		$form->add('to', 'text', 'To', array('attr' => array('placeholder' => 'To...')));

		return $form;
	}
}