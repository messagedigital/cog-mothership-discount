<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;
use Message\Mothership\Discount\Bundle\Exception\BundleBuildException;

class Bundle extends Controller
{

	public function create()
	{
		$form = $this->createForm($this->get('discount.bundle.form.bundle'));

		return $this->render('Message:Mothership:Discount::bundle:create', [
			'form' => $form,
			'currencies' => $this->get('cfg')->currency->supportedCurrencies,
		]);
	}

	public function createAction()
	{
		$form = $this->createForm($this->get('discount.bundle.form.bundle'));
		$form->handleRequest();
		$data = $form->getData();

		if ($form->isValid()) {
			try {
				$bundle = $this->get('discount.bundle_factory')->build($data);
				$this->get('discount.bundle_create')->save($bundle);
			} catch (BundleBuildException $e) {
				$this->addFlash('error', $this->trans('ms.discount.bundle.error.build', [
					'%message%' => $e->getMessage()
				]));

				return $this->redirectToReferer();
			}
		}

		return $this->redirectToReferer();
	}
}