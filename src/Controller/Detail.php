<?php

namespace Message\Mothership\Discount\Controller;

use Message\Mothership\Discount\Discount;
use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;

class Detail extends Controller
{
	public function attributes($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);
		// this is necessary to not display changes in the discount object, before it's persisted
		$viewDiscount = clone $discount;

		$form = $this->createForm($this->get('discount.form.discount.attributes'), $discount);
		$form->handleRequest();

		if ($form->isValid()) {
			$discount = $form->getData();
            $discount = $this->get('discount.edit')->save($discount);

            if ($discount->id) {
				$this->addFlash('success', $this->trans('ms.discount.discount.edit.success', array(
					'%name%' => $discount->name,
				)));
            }
		}

		return $this->render('::attributes', array(
			'discount'  => $viewDiscount,
			'form'  	=> $form,
		));
	}

	public function benefit($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		$form = $this->createForm($this->get('discount.form.discount.benefit'), $discount);

		$form->handleRequest();

		if ($form->isValid()) {
			$discount = $form->getData();
            $discount = $this->get('discount.edit')->save($discount);

            if ($discount->id) {
				$this->addFlash('success', $this->trans('ms.discount.discount.edit.success', array(
					'%name%' => $discount->name,
				)));
            }
		}

		return $this->render('::benefit', array(
			'discount'  => $discount,
			'form'  	=> $form,
		));
	}

	public function criteria($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		$form = $this->createForm($this->get('discount.form.discount.criteria'), $discount);

		$form->handleRequest();

		if ($form->isValid()) {

            $discount = $this->get('discount.edit')->save($discount);

            if ($discount->id) {
				$this->addFlash('success', $this->trans('ms.discount.discount.edit.success', array(
					'%name%' => $discount->name,
				)));
            }
		}

		return $this->render('::criteria', array(
			'discount'  => $discount,
			'form'  	=> $form,
		));
	}

	public function orders($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);
		$orderDiscounts = $this->get('order.discount.loader')->getByCode($discount->code);

		$totalDiscount 	= 0;
		$totalGross		= 0;
		foreach ($orderDiscounts as $orderDiscount) {
			$order = $orderDiscount->order;

			// check this with the currency set on the system!
			if($order->currencyID !== 'GBP') {
				$discountAmount = $orderDiscount->amount * $order->conversionRate;
				$gross          = $order->totalGross * $order->conversionRate;
			} else {
				$discountAmount = $orderDiscount->amount;
				$gross          = $order->totalGross;
			}

			$totalDiscount 	+= $discountAmount;
			$totalGross 	+= $gross;
		}

		return $this->render('::orders', array(
			'discount' 			=> $discount,
			'orderDiscounts' 	=> $orderDiscounts,
			'totalDiscount'		=> $totalDiscount,
			'totalGross'		=> $totalGross,
			'topbarClass'		=> 'ctl-clear',
		));
	}

	public function tabs($discountID, $topbarClass = '')
	{
		$tabs = array(
			'Attributes' => $this->generateUrl('ms.cp.discount.edit', 			array('discountID' => $discountID)),
			'Benefit'	 => $this->generateUrl('ms.cp.discount.edit.benefit', 	array('discountID' => $discountID)),
			'Criteria' 	 => $this->generateUrl('ms.cp.discount.edit.criteria',  array('discountID' => $discountID)),
			'Orders'  	 => $this->generateUrl('ms.cp.discount.view.orders', 	array('discountID' => $discountID)),
		);

		$current = ucfirst(trim(strrchr($this->get('http.request.master')->get('_controller'), '::'), ':'));

		return $this->render('Message:Mothership:Discount::tabs', array(
			'tabs'    	  => $tabs,
			'current' 	  => $current,
			'discountID'  => $discountID,
			'topbarClass' => $topbarClass,
		));
	}


	/**
	 * Delete a discount
	 *
	 * @param  int 	$discountID id of the discount to be marked as deleted
	 */
	public function delete($discountID)
	{
		// Check that the delete request has been sent
		if ($delete = $this->get('request')->get('delete')) {
			$discount = $this->get('discount.loader')->getByID($discountID);

			if ($discount = $this->get('discount.delete')->delete($discount)) {
				$this->addFlash(
					'success',
					sprintf(
						'%s was deleted. <a href="%s">Undo</a>',
						$discount->name,
						$this->generateUrl('ms.cp.discount.restore', array('discountID' => $discount->id))
					)
				);
			} else {
				$this->addFlash('error', sprintf('%s could not be deleted.', $discount->name));
			}

		}
		return $this->redirect($this->generateUrl('ms.cp.discount.dashboard'));
	}

	/**
	 * Restore an discount that has been deleted.
	 *
	 * @param  int $discountID	id of the discount to be restored
	 */
	public function restore($discountID)
	{
		// Load the discount
		$discount = $this->get('discount.loader')->includeDeleted(true)->getByID($discountID);

		if ($this->get('discount.delete')->restore($discount)) {
			$this->addFlash('success', sprintf('%s was restored successfully', $discount->name));
		} else {
			$this->addFlash('error', sprintf('%s could not be restored.', $discount->name));
		}

		return $this->redirect($this->generateUrl('ms.cp.discount.dashboard'));
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

			if ($discount->start !== null && $discount->end !== null && $discount->start > $discount->end) {
				$this->addFlash('error', 'Start date must be before end date!');
			} else {
				$discount = $this->get('discount.edit')->save($discount);

				$this->addFlash('success', sprintf('You successfully saved discount attributes for discount "%s".', $discount->name));
				return $this->redirectToRoute('ms.cp.discount.edit', array('discountID' => $discount->id));
			}
		}

		return $this->render('::attributes', array(
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

			foreach ($data['discountAmounts'] as $currencyID => $amount) {
				if ($amount !== null) {
					$discountAmount = new Discount\DiscountAmount;
					$discountAmount->currencyID = $currencyID;
					$discountAmount->amount = $amount;

					// TODO uselocale!
					$discountAmount->locale = 'en_GB';

					$discount->addDiscountAmount($discountAmount);
				}
			}

			// TODO Replace this with form validation!
			if ($discount->percentage === null && count($discount->discountAmounts) === 0 && !$discount->freeShipping) {
				$this->addFlash('error', 'Neither a percentage discount, nor a fixed discount amount, nor free shipping has been entered for this discount!');
			} else if ($discount->percentage !== null && count($discount->discountAmounts) > 0) {
				$this->addFlash('error', 'Please either enter a percentage discount OR a fixed discount amount!');
			} else {
				$discount = $this->get('discount.edit')->save($discount);

				$this->addFlash('success', sprintf('You successfully saved benefits for discount "%s".', $discount->name));
				return $this->redirectToRoute('ms.cp.discount.edit.benefit', array('discountID' => $discount->id));
			}
		}

		return $this->render('::benefit', array(
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
			foreach ($data['thresholds'] as $currencyID => $thresholdAmount) {
				if ($thresholdAmount !== null) {
					$threshold = new Discount\Threshold;
					$threshold->currencyID = $currencyID;
					$threshold->threshold = $thresholdAmount;

					// TODO LOCALE!
					$threshold->locale = 'en_GB';

					$discount->addThreshold($threshold);
				}
			}

			foreach ($data['products'] as $productID) {
				$discount->addProduct($this->get('product.loader')->getByID($productID));
			}

			$discount = $this->get('discount.edit')->save($discount);

			$this->addFlash('success', sprintf('You successfully saved criteria for discount "%s".', $discount->name));
			return $this->redirectToRoute('ms.cp.discount.edit.criteria', array('discountID' => $discount->id));

		}

		return $this->render('::criteria', array(
			'discount'  => $discount,
			'form'  	=> $form,
		));
	}

	protected function _getAttributesForm($discount)
	{
		$products = $this->get('product.loader')->getAll();

		$form = $this->get('form')
			->setName('discount-edit')
			->setAction($this->generateUrl('ms.cp.discount.edit.action', array('discountID' => $discount->id)))
			->setMethod('post');

		$form->add('name', 'text', 'Name', array('data' =>  $discount->name))
			->val()
			->titlecase()
			->maxLength(255);

		$form->add('description', 'textarea', 'Description', array('data' => $discount->description))
			->val()->optional();

		$form
			->add(
				'start',
				'datetime',
				'Start date',
				array(
		    		'data' => $discount->start
	    		)
	    	)
    		->val()
    		->optional();

		$form
			->add(
				'end',
				'datetime',
				'End date',
				array(
		    		'data' => $discount->end
	    		)
	    	)
    		->val()
    		->optional();

		return $form;
	}

	protected function _getBenefitForm($discount)
	{
		// TODO: Replace with actual currency collection!
		$currencies = array('GBP');

		$form = $this->get('form')
			->setName('benefit-edit')
			->setAction($this->generateUrl('ms.cp.discount.edit.benefit.action', array('discountID' => $discount->id)))
			->setMethod('post');

		$form->add('percentage', 'percent', 'Percentage Discount Amount', array('type' => 'integer', 'data' =>  $discount->percentage))
			->val()
			->number()
			->max(100)
			->min(0)
			->optional();

		$form->add('freeShipping', 'checkbox', 'Free Shipping', array('data' =>  $discount->freeShipping))
			->val()
			->optional();

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
					'currency' => $currencyID,
					'data' => $discount->getDiscountAmountForCurrencyID($currencyID),
				)
			)
			->val()
			->number()
			->error(sprintf('Fixed Discount Amount in %s must be numeric.', $currencyID))
			->min(0)
			->error(sprintf('Fixed Discount Amount in %s cannot be negative.', $currencyID))
			->optional();
		}
		$form->add($discountAmountsForm, 'form');

		return $form;
	}

	protected function _getCriteriaForm($discount)
	{
		// TODO: Replace with actual currency collection!
		$currencies = array('GBP');
		$products = $this->get('product.loader')->getAll();

		$form = $this->get('form')
			->setName('criteria-edit')
			->setAction($this->generateUrl('ms.cp.discount.edit.criteria.action', array('discountID' => $discount->id)))
			->setMethod('post');

		$form->add('appliesTo', 'choice', 'Applies to', array(
			'required' 	=> true,
			'choices' 	=> array('Specific Products Only', 'Whole Order'),
			'multiple' 	=> false,
			'expanded' 	=> false,
			'data' 		=> $discount->appliesToOrder,
		));

		$thresholdsForm = $this->get('form')
			->setName('thresholds')
			->addOptions(array(
				'label' => 'Threshold',
				'auto_initialize' => false
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
			)
			->val()
			->min(0)
			->error(sprintf('Threshold in %s cannot be negative.', $currencyID))
			->number()
			->error(sprintf('Threshold in %s must be numeric.', $currencyID))
			->optional();
		}

		$form->add($thresholdsForm, 'form');

		$productChoices = array();
		$productSelection = array();

		foreach ($products as $product) {
			$productChoices[] = array($product->id => $product->name);
		}

		foreach ($discount->products as $product) {
			$productSelection[] = $product->id;
		}

		$form->add('products', 'choice', 'Products', array(
		    'choices'   => $productChoices,
		    'multiple'  => true,
		    'expanded'  => true,
		    'required'  => false,
		    'data'		=> $productSelection,
		))
			->val()
			->optional();

		return $form;
	}
}
