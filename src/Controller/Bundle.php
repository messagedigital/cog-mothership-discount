<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;
use Message\Mothership\Discount\Bundle\Exception\BundleBuildException;

class Bundle extends Controller
{
	const INVALID_BUNDLE_SESSION = 'discount.bundle.invalid';

	public function create()
	{
		$data = $this->get('http.session')->get(self::INVALID_BUNDLE_SESSION) ?: null;
		$this->get('http.session')->remove(self::INVALID_BUNDLE_SESSION);

		$form = $this->createForm($this->get('discount.bundle.form.bundle'), $data);

		return $this->render('Message:Mothership:Discount::bundle:create', [
			'form'       => $form,
			'currencies' => $this->get('cfg')->currency->supportedCurrencies,
			'bundle'     => $data
		]);
	}

	public function createAction()
	{
		$form = $this->createForm($this->get('discount.bundle.form.bundle'));
		$form->handleRequest();

		$bundle = $form->getData();

		if ($form->isValid()) {
			try {
				$bundle = $this->get('discount.bundle_create')->save($bundle);
				$this->addFlash('success', $this->trans('ms.discount.bundle.create.success'));

				return $this->redirectToRoute('ms.cp.discount.bundle.edit', [
					'bundleID' => $bundle->id,
				]);
			} catch (BundleBuildException $e) {
				$this->addFlash('error', $this->trans('ms.discount.bundle.error.build', [
					'%message%' => $e->getMessage()
				]));

				return $this->_redirectInvalid($bundle);
			}
		}

		return $this->_redirectInvalid($bundle);
	}

	public function edit($bundleID)
	{
		$bundle = $this->get('discount.bundle_loader')->getByID($bundleID);

		$form = $this->createForm($this->get('discount.bundle.form.bundle'), $bundle);

		return $this->render('Message:Mothership:Discount::bundle:edit', [
			'form' => $form,
			'currencies' => $this->get('cfg')->currency->supportedCurrencies,
			'bundle' => $bundle
		]);
	}

	public function editAction($bundleID)
	{
		$form = $this->createForm($this->get('discount.bundle.form.bundle'));
		$form->handleRequest();

		$bundle = $form->getData();

		if ($form->isValid()) {
			$this->get('discount.bundle_edit')->save($bundle);
			$this->addFlash('success', 'ms.discount.bundle.edit.success');

			return $this->redirectToReferer();
		}

		return $this->_redirectInvalid($bundle);
	}

	private function _redirectInvalid($data)
	{
		$this->get('http.session')->set(self::INVALID_BUNDLE_SESSION, $data);

		return $this->redirectToReferer();
	}
}