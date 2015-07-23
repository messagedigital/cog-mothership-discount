<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;
use Message\Mothership\Discount\Form\BundleForm;

class Bundle extends Controller
{
	const INVALID_BUNDLE_SESSION = 'discount.bundle.invalid';

	public function create()
	{
		$data = $this->_getInvalidBundleData();
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

			if (is_array($bundle)) {
				return $this->_redirectBundleBuildFail($bundle);
			}

			$bundle = $this->get('discount.bundle_create')->save($bundle);
			$this->addFlash('success', $this->trans('ms.discount.bundle.create.success'));

			return $this->redirectToRoute('ms.cp.discount.bundle.edit', [
				'bundleID' => $bundle->getID(),
			]);

		}

		return $this->_redirectInvalid($bundle);
	}

	public function edit($bundleID)
	{
		$bundle = $this->_getInvalidBundleData($bundleID) ?: $this->get('discount.bundle_loader')->getByID($bundleID);

		$form = $this->createForm($this->get('discount.bundle.form.bundle'), $bundle);

		return $this->render('Message:Mothership:Discount::bundle:edit', [
			'form' => $form,
			'currencies' => $this->get('cfg')->currency->supportedCurrencies,
			'bundle' => $bundle
		]);
	}

	public function editAction()
	{
		$form = $this->createForm($this->get('discount.bundle.form.bundle'));

		$form->handleRequest();

		$bundle = $form->getData();

		if ($form->isValid()) {

			if (is_array($bundle)) {
				return $this->_redirectBundleBuildFail($bundle);
			}

			$this->get('discount.bundle_edit')->save($bundle);
			$this->addFlash('success', $this->trans('ms.discount.bundle.edit.success'));

			return $this->redirectToReferer();
		}

		return $this->_redirectInvalid($bundle);
	}

	public function listing()
	{
		$bundles = $this->get('discount.bundle_loader')->getAll();

		return $this->render('Message:Mothership:Discount::bundle:listing', [
			'bundles' => $bundles,
			'currency' => $this->get('currency'),
		]);
	}

	public function delete($bundleID)
	{
		$bundle = $this->get('discount.bundle_loader')->getByID($bundleID);

		$this->get('discount.bundle_delete')->delete($bundle);
		$this->addFlash('success', $this->trans('ms.discount.bundle.delete.success', [
			'%url%' => $this->generateUrl('ms.cp.discount.bundle.restore', ['bundleID' => $bundleID])
		]));

		return $this->redirectToReferer();
	}

	public function restore($bundleID)
	{
		$bundle = $this->get('discount.bundle_loader')->includeDeleted()->getByID($bundleID);

		$this->get('discount.bundle_delete')->restore($bundle);
		$this->addFlash('success', $this->trans('ms.discount.bundle.restore.success'));

		return $this->redirectToReferer();

	}

	private function _getInvalidBundleData($bundleID = null)
	{
		$data = $this->get('http.session')->get(self::INVALID_BUNDLE_SESSION) ?: null;

		if (null === $data) {
			return null;
		}

		if ($bundleID && (!array_key_exists(BundleForm::ID, $data) || $data[BundleForm::ID] != $bundleID)) {
			return null;
		}

		$this->get('http.session')->remove(self::INVALID_BUNDLE_SESSION);

		return $data;
	}

	/**
	 * If the `Form\DataTransformer\BundleTransformer` catches a `BundleBuildException` when trying to transform the
	 * data, it will add another value to the data array with a key of `error`, which will be the message from the
	 * caught exception. This method adds this error message to the flash bag and redirects to the referer.
	 *
	 * @param array $data
	 *
	 * @return \Message\Cog\HTTP\RedirectResponse
	 */
	private function _redirectBundleBuildFail(array $data)
	{
		if (!array_key_exists('error', $data)) {
			throw new \LogicException('No error set on invalid bundle data');
		}

		$this->addFlash('error', $this->trans('ms.discount.bundle.error.build', [
			'%message%' => $data['error'],
		]));

		unset($data['error']);

		return $this->_redirectInvalid($data);
	}

	private function _redirectInvalid($data)
	{
		$this->get('http.session')->set(self::INVALID_BUNDLE_SESSION, $data);

		return $this->redirectToReferer();
	}
}