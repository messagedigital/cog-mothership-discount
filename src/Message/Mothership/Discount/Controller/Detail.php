<?php

namespace Message\Mothership\Discount\Controller;

use Message\Mothership\Discount\Discount;
use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;

class Detail extends Controller
{
	public function index($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		return $this->render('::discount:attributes', array(
			'discount'  => $discount,
			'form'  	=> $this->_getAttributesForm($discount),
		));
	}

	public function benefit($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		return $this->render('::discount:benefit', array(
			'discount'  => $discount,
			'form'  	=> $this->_getBenefitForm($discount),
		));
	}

	public function criteria($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		return $this->render('::discount:criteria', array(
			'discount'  => $discount,
			'form'  	=> $this->_getCriteriaForm($discount),
		));
	}

	public function orders($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);
		$orderDiscounts = $this->get('order.discount.loader')->getByCode($discount->code);

		$totalDiscount 	= 0;
		$totalGross		= 0;
		foreach($orderDiscounts as $orderDiscount) {
			// ADD CONVERSION HERE
			$totalDiscount 	+= $orderDiscount->amount;
			$totalGross 	+= $orderDiscount->order->totalGross;
		}
		
		return $this->render('::discount:orders', array(
			'discount' 			=> $discount,
			'orderDiscounts' 	=> $orderDiscounts,
			'totalDiscount'		=> $totalDiscount,
			'totalGross'		=> $totalGross,
		));
	}

	public function processAttributes($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		$form = $this->_getAttributesForm($discount);
		if ($form->isValid() && $data = $form->getFilteredData()) {
			$discount->name 		= $data['name'];
			$discount->description 	= $data['description'];

			$discount->start = ($data['start'] !== null ? $data['start'] : null);
			$discount->end   = ($data['end']   !== null ? $data['end']   : null);

			if(!$discount->hasValidStartEnd()) {
				$this->addFlash('error', 'Start date must be before end date!');
			} else {
				$discount = $this->get('discount.edit')->save($discount);

				$this->addFlash('success', sprintf('You successfully saved discount attributes for discount "%s".', $discount->name));
				return $this->redirectToRoute('ms.discount.edit', array('discountID' => $discount->id));
			}			
		}

		return $this->render('::discount:attributes', array(
			'discount'  => $discount,
			'form'  	=> $form,
		));
	}

	public function processBenefit($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);
		$discount->discountAmounts = array();


		$form = $this->_getBenefitForm($discount);
		if ($form->isValid() && $data = $form->getFilteredData()) {

			$discount->percentage   = ($data['percentage'] !== null ? $data['percentage'] : null);
			$discount->freeShipping = $data['freeShipping'];

			foreach($data['discountAmounts'] as $currencyID => $amount) {
				if($amount !== null) {
					$discountAmount = new Discount\DiscountAmount;
					$discountAmount->currencyID = $currencyID;
					$discountAmount->amount = $amount;

					// TODO LOCALE?!?
					$discountAmount->locale = 'en_GB';

					$discount->addDiscountAmount($discountAmount);
				}
			}

			// TODO Replace this with form validation!
			if(!$discount->hasBenefit()) {
				$this->addFlash('error', 'Neither a percentage discount, nor a fixed discount amount, nor free shipping has been entered for this discount!');
			} else if(!$discount->hasValidBenefit()) {
				$this->addFlash('error', 'Please either enter a percentage discount OR a fixed discount amount!');				
			} else {
				$discount = $this->get('discount.edit')->save($discount);

				$this->addFlash('success', sprintf('You successfully saved benefits for discount "%s".', $discount->name));
				return $this->redirectToRoute('ms.discount.edit.benefit', array('discountID' => $discount->id));
			}
		}

		return $this->render('::discount:benefit', array(
			'discount'  => $discount,
			'form'  	=> $form,
		));
	}

	public function processCriteria($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);
		$discount->thresholds = array();
		$discount->products   = array();

		$form = $this->_getCriteriaForm($discount);

		if ($form->isValid() && $data = $form->getFilteredData()) {
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

			foreach($data['products'] as $productID) {
				$discount->addProduct($this->get('product.loader')->getByID($productID));
			}

			$discount = $this->get('discount.edit')->save($discount);

			$this->addFlash('success', sprintf('You successfully saved criteria for discount "%s".', $discount->name));
			return $this->redirectToRoute('ms.discount.edit.criteria', array('discountID' => $discount->id));

		}

		return $this->render('::discount:criteria', array(
			'discount'  => $discount,
			'form'  	=> $form,
		));
	}

	protected function _getAttributesForm($discount)
	{
		// TODO: Add validation for percentage / discount amount -> only one of them should be filled in!

		$products = $this->get('product.loader')->getAll();
		// TODO: Replace with actual currency collection!
		$currencies = array('GBP');

		$form = $this->get('form')
			->setName('discount-edit')
			->setAction($this->generateUrl('ms.discount.edit.action', array('discountID' => $discount->id)))
			->setMethod('post');

		$form->add('name', 'text', 'Name', array('data' =>  $discount->name))
			->val()
			->maxLength(255);

		$form->add('description', 'textarea', 'Description', array('data' => $discount->description))
			->val()->optional();

		$dateEmptyValues = array(
			'year' 	 => 'Year',
			'month'  => 'Month',
			'day' 	 => 'Day',
			'hour' 	 => 'Hour',
			'minute' => 'Minute'
		);

		$form->add('start', 'datetime', 'Start', array('empty_value' => $dateEmptyValues, 'data' =>  $discount->start))
			->val()->optional();

		$form->add('end', 'datetime', 'End', array('empty_value' => $dateEmptyValues, 'data' =>  $discount->end))
			->val()->optional();

		return $form;
	}

	protected function _getBenefitForm($discount)
	{
		// TODO: Replace with actual currency collection!
		$currencies = array('GBP');

		$form = $this->get('form')
			->setName('benefit-edit')
			->setAction($this->generateUrl('ms.discount.edit.benefit.action', array('discountID' => $discount->id)))
			->setMethod('post');

		$form->add('percentage', 'percent', 'Percentage Discount Amount', array('type' => 'integer', 'data' =>  $discount->percentage))
			->val()
			->max(100)
			->min(0)
			->optional();

		$form->add('freeShipping', 'checkbox', 'Free Shipping', array('data' =>  $discount->freeShipping))
			->val()->optional();

		$discountAmountsForm = $this->get('form')
			->setName('discountAmounts')
			->addOptions(array(
				'label' => 'Fixed Discount Amount',
				'auto_initialize' => false,
			));

		foreach ($currencies as $currencyID) {
			$discountAmountsForm->add(
				$currencyID,
				'money',
				$currencyID,
				array(
					'label' => $currencyID,
					'currency' => $currencyID,
					'data' => $discount->getDiscountAmountForCurrencyID($currencyID),
				)
			)->val()
				->min(0)
				->optional();
		}

		$form->add($discountAmountsForm->getForm(), 'form');

		return $form;
	}

	protected function _getCriteriaForm($discount)
	{
		// TODO: Replace with actual currency collection!
		$currencies = array('GBP');
		$products = $this->get('product.loader')->getAll();

		$form = $this->get('form')
			->setName('criteria-edit')
			->setAction($this->generateUrl('ms.discount.edit.criteria.action', array('discountID' => $discount->id)))
			->setMethod('post');

		$form->add('appliesTo', 'choice', 'Applies to', array(
			'required' 	=> true,
			'choices' 		=> array('Specific Products Only', 'Whole Order'),
			'multiple' 		=> false,
			'expanded' 		=> false,
			'data' 			=> $discount->appliesToOrder,
		));

		$thresholdsForm = $this->get('form')
			->setName('thresholds')
			->addOptions(array(
				'label' => 'Threshold',
				'auto_initialize' => false,
			));


		foreach ($currencies as $currencyID) {
			$thresholdsForm->add(
				$currencyID,
				'money',
				$currencyID,
				array(
					'label' => $currencyID,
					'currency' => $currencyID,
					'data' => $discount->getThresholdForCurrencyID($currencyID),
				)
			)->val()
				->optional();
		}


		$form->add($thresholdsForm->getForm(), 'form');

		$productChoices = array();
		$productSelection = array();

		foreach($products as $product) {
			$productChoices[] = array($product->id => $product->name);
		}

		foreach($discount->products as $product) {
			$productSelection[] = $product->id;
		}

		$form->add('products', 'choice', 'Products', array(
		    'choices'   => $productChoices,
		    'multiple'  => true,
		    'expanded'  => true,
		    'required'  => false,
		    'data'		=> $productSelection,
		));

		return $form;
	}
}
