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
		// when we pass the same object to the view, the title of the side will
		// change when the form is changed, even if there are form errors.
		$viewDiscount = clone $discount;

		$form = $this->createForm($this->get('discount.form.attributes'), $discount);
		$form->handleRequest();

		if ($form->isValid()) {
			$discount = $form->getData();

			$discount = $this->get('discount.edit')->save($discount);

			$this->addFlash('success', $this->trans('ms.discount.discount.edit.success', array(
				'%name%' => $discount->name,
			)));
		}

		return $this->render('::attributes', array(
			'discount'  => $viewDiscount,
			'form'  	=> $form,
		));
	}

	public function benefit($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		$form = $this->createForm($this->get('discount.form.benefit'), $discount);

		$form->handleRequest();

		if ($form->isValid()) {
			$discount = $form->getData();
			$discount = $this->get('discount.edit')->save($discount);

			$this->addFlash('success', $this->trans('ms.discount.discount.edit.success', array(
				'%name%' => $discount->name,
			)));
		}

		return $this->render('::benefit', array(
			'discount'  => $discount,
			'form'  	=> $form,
		));
	}

	public function criteria($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);

		$form = $this->createForm($this->get('discount.form.criteria'), $discount);

		$form->handleRequest();

		if ($form->isValid()) {

			$discount = $this->get('discount.edit')->save($discount);

			$this->addFlash('success', $this->trans('ms.discount.discount.edit.success', array(
				'%name%' => $discount->name,
			)));
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

		$totalDiscount 	= [];
		$totalGross		= [];
		foreach ($orderDiscounts as $orderDiscount) {
			$order = $orderDiscount->order;

			if(isset($totalDiscount[$order->currencyID])) {
				$totalDiscount[$order->currencyID] += $orderDiscount->amount;
			} else {
				$totalDiscount[$order->currencyID] = $orderDiscount->amount;
			}

			if(isset($totalGross[$order->currencyID])) {
				$totalGross[$order->currencyID] += $order->totalGross;
			} else {
				$totalGross[$order->currencyID] = $order->totalGross;
			}
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
}
