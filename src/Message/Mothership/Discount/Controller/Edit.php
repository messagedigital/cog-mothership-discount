<?php

namespace Message\Mothership\Discount\Controller;

use Message\Mothership\Discount\Discount;
use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;

class Edit extends Controller
{
	public function index($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		return $this->render('::discount:edit-attributes', array(
			'discount'  => $discount,
			'form'  	=> $this->_getAttributesForm($discount),
		));
	}

	public function discountDetails($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		return $this->render('::discount:edit-discount-details', array(
			'discount'  => $discount,
			'form'  	=> $this->_getDiscountDetailsForm($discount),
		));
	}

	public function products($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		return $this->render('::discount:edit-products', array(
			'discount'  => $discount,
			'form'  	=> $this->_getProductsForm($discount),
		));
	}

	public function processAttributes($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		$form = $this->_getAttributesForm($discount);
		if ($form->isValid() && $data = $form->getFilteredData()) {
			$discount->code 		= $data['code'];
			$discount->name 		= $data['name'];
			$discount->description 	= $data['description'];

			$discount->start = ($data['start'] !== null ? $data['start'] : null);
			$discount->end   = ($data['end']   !== null ? $data['end']   : null);

			$discount = $this->get('discount.edit')->save($discount);

			$this->addFlash('success', sprintf('You successfully saved discount attributes for discount "%s".', $discount->name));
			return $this->redirectToRoute('ms.discount.edit', array('discountID' => $discount->id));
		}

		return $this->render('::discount:edit-attributes', array(
			'discount'  => $discount,
			'form'  	=> $form,
		));
	}

	public function processDiscountDetails($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);
		$discount->thresholds 		= array();
		$discount->discountAmounts  = array();


		$form = $this->_getDiscountDetailsForm($discount);
		if ($form->isValid() && $data = $form->getFilteredData()) {

			$discount->percentage   = ($data['percentage'] !== null ? $data['percentage'] : null);
			$discount->freeShipping = $data['freeShipping'];

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
					$discountAmount->locale = 'en_GB';

					$discount->addDiscountAmount($discountAmount);
				}
			}

			$discount = $this->get('discount.edit')->save($discount);

			$this->addFlash('success', sprintf('You successfully saved discount details for discount "%s".', $discount->name));
			return $this->redirectToRoute('ms.discount.edit.discount-details', array('discountID' => $discount->id));

		}

		return $this->render('::discount:edit-discount-details', array(
			'discount'  => $discount,
			'form'  	=> $form,
		));
	}

	public function processProducts($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);
		$discount->products = array();

		$form = $this->_getProductsForm($discount);

		if ($form->isValid() && $data = $form->getFilteredData()) {
			foreach($data['products'] as $productID) {
				$discount->addProduct($this->get('product.loader')->getByID($productID));
			}

			$discount = $this->get('discount.edit')->save($discount);

			$this->addFlash('success', sprintf('You successfully saved products for discount "%s".', $discount->name));
			return $this->redirectToRoute('ms.discount.edit.products', array('discountID' => $discount->id));

		}

		return $this->render('::discount:edit-products', array(
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

		$form->add('name', 'text', 'Name', array('attr' => array('value' =>  $discount->name)))
			->val()
			->maxLength(255);

		$form->add('description', 'textarea', 'Description', array('data' => $discount->description))
			->val()->optional();

		$form->add('code', 'text', 'Code', array('attr' => array('value' =>  $discount->code)))
			->val()->maxLength(10);

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

		$form->add('appliesTo', 'choice', 'Applies to', array(
			'required' 	=> true,
			'choices' 		=> array('Specific Products Only', 'Whole Order'),
			'multiple' 		=> false,
			'expanded' 		=> false,
			'data' 			=> $discount->appliesToOrder,
		));

		return $form;
	}

	protected function _getDiscountDetailsForm($discount)
	{
		// TODO: Add validation for percentage / discount amount -> only one of them should be filled in!
		// TODO: Replace with actual currency collection!
		$currencies = array('GBP');

		$form = $this->get('form')
			->setName('discount-detail-edit')
			->setAction($this->generateUrl('ms.discount.edit.discount-details.action', array('discountID' => $discount->id)))
			->setMethod('post');

		$form->add('percentage', 'percent', 'Percentage Discount Amount', array('type' => 'integer', 'attr' => array('value' =>  $discount->percentage)))
			->val()
			->optional();

		$form->add('freeShipping', 'checkbox', 'Free Shipping', array('data' =>  $discount->freeShipping))
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

		foreach ($currencies as $currencyID) {
			$thresholdsForm->add(
				$currencyID,
				'money',
				$currencyID,
				array(
					'label' => $currencyID,
					'currency' => $currencyID,
					'attr' => array(
						'value' => $discount->getThresholdForCurrencyID($currencyID),
					)
				)
			);
			$discountAmountsForm->add(
				$currencyID,
				'money',
				$currencyID,
				array(
					'label' => $currencyID,
					'currency' => $currencyID,
					'attr' => array(
						'value' => $discount->getDiscountAmountForCurrencyID($currencyID),
					)
				)
			);
		}

		$form->add($thresholdsForm->getForm(), 'form');
		$form->add($discountAmountsForm->getForm(), 'form');

		return $form;
	}

	protected function _getProductsForm($discount)
	{
		$products = $this->get('product.loader')->getAll();

		$form = $this->get('form')
			->setName('products-edit')
			->setAction($this->generateUrl('ms.discount.edit.products.action', array('discountID' => $discount->id)))
			->setMethod('post');

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
