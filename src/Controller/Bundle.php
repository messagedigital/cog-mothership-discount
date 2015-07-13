<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;

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
}