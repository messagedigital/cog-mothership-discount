<?php

namespace Message\Mothership\Discount\Controller;

use Message\Mothership\Discount\Discount;
use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;

class Create extends Controller
{
	public function index()
	{
		return $this->render('::discount:create', array(
			'form'  => $this->_getForm(),
		));
	}

	public function process()
	{
		$form = $this->_getForm();
		if ($form->isValid() && $data = $form->getFilteredData()) {
			$discount = new Discount\Discount;

			$discount->code 		= $data['code'];
			$discount->name 		= $data['name'];
			$discount->description 	= $data['description'];

			$discount->authorship->create(new DateTimeImmutable, $this->get('user.current')->id);

			$discount->start = ($data['start'] !== null ? $data['start'] : null);
			$discount->end   = ($data['end'] !== null ? $data['end'] : null);

			$discount->percentage   = ($data['percentage'] !== null ? $data['percentage'] : null);
			$discount->freeShipping = $data['freeShipping'];

			foreach($data['products'] as $productID) {
				$discount->addProduct($this->get('product.loader')->getByID($productID));
			}

			foreach($data['thresholds'] as $currencyID => $thresholdAmount) {
				if($thresholdAmount !== null) {
					$threshold = new Discount\Threshold;
					$threshold->currencyID = $currencyID;
					$threshold->threshold = $thresholdAmount;

					// TODO LOCALE?!?
					$threshold->locale = 'en_GB';

					$discount->addThreshold($threshold);
				}
			}

			foreach($data['discountAmounts'] as $currencyID => $amount) {
				if($amount !== null) {
					$discountAmount = new Discount\DiscountAmount;
					$discountAmount->currencyID = $currencyID;
					$discountAmount->amount = $amount;

					// TODO LOCALE?!?
					$threshold->locale = 'en_GB';

					$discount->addDiscountAmount($discountAmount);
				}
			}

			$discount = $this->get('discount.create')->create($discount);

			if($discount->id) {
				$this->addFlash('success', 'You successfully added a discount!');
				$this->redirect('ms.discount.create');
			}
		}

		return $this->render('::discount:create', array(
			'form'  => $form,
		));
	}

	protected function _getForm()
	{
		// TODO: Add validation for percentage / discount amount -> only one of them should be filled in!

		$products = $this->get('product.loader')->getAll();
		// TODO: Replace with actual currency collection!
		$currencies = array('GBP');

		$form = $this->get('form')
			->setName('discount-create')
			->setAction($this->generateUrl('ms.discount.create.action'))
			->setMethod('post');

		$form->add('name', 'text', 'Name')
			->val()
			->maxLength(255);

		$form->add('description', 'textarea', 'Description')
			->val()->optional();

		$form->add('code', 'text', 'Code')
			->val()->maxLength(10);

		$dateEmptyValues = array(
			'year' 	 => 'Year',
			'month'  => 'Month',
			'day' 	 => 'Day',
			'hour' 	 => 'Hour',
			'minute' => 'Minute'
		);

		$form->add('start', 'datetime', 'Start', array('empty_value' => $dateEmptyValues))
			->val()->optional();

		$form->add('end', 'datetime', 'End', array('empty_value' => $dateEmptyValues))
			->val()->optional();

		$form->add('percentage', 'percent', 'Percentage Discount Amount')
			->val()
			->optional();

		$form->add('freeShipping', 'checkbox', 'Free Shipping')
			->val()->optional();


		$thresholdsForm = $this->get('form')
			->setName('thresholds')
			->addOptions(array(
				'label' => 'Threshold',
				'auto_initialize' => false,
				'required' => false,
			));

		$discountAmountsForm = $this->get('form')
			->setName('discountAmounts')
			->addOptions(array(
				'label' => 'Fixed Discount Amount',
				'auto_initialize' => false,
				'required' => false,
			));

		foreach ($currencies as $currency) {
			$thresholdsForm->add($currency, 'money', $currency, array('label' => $currency, 'currency' => $currency));

			$discountAmountsForm->add($currency, 'money', $currency, array('label' => $currency, 'currency' => $currency));
		}

		$productChoices = array();
		foreach($products as $product) {
			$productChoices[] = array($product->id => $product->name);
		}

		$form->add('products', 'choice', 'Products', array(
		    'choices'   => $productChoices,
		    'multiple'  => true,
		    'expanded'  => true,
		    'required'  => false
		));

		$form->add($thresholdsForm->getForm(), 'form');
		$form->add($discountAmountsForm->getForm(), 'form');

		return $form;
	}
}
