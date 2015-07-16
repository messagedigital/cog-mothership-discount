<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Transaction;
use Message\Cog\DB\TransactionalInterface;
use Message\User\UserInterface;

class Edit implements TransactionalInterface
{
	private $_transaction;

	private $productCreate;

	private $priceCreate;

	private $imageCreate;

	private $_user;

	private $_transactionOverride = false;

	public function __construct(
		Transaction $transaction,
		BundleProductCreate $productCreate,
		BundlePriceCreate $priceCreate,
		BundleImageCreate $imageCreate,
		UserInterface $user
	)
	{
		$this->_transaction = $transaction;
		$this->_productCreate = $productCreate;
		$this->_priceCreate = $priceCreate;
		$this->_imageCreate = $imageCreate;
		$this->_user = $user;
	}

	public function setTransaction(Transaction $transaction)
	{
		$this->_transaction = $transaction;
		$this->_transactionOverride = true;
	}

	public function save(Bundle $bundle)
	{
		$this->_transaction->add("
			UPDATE
				discount_bundle
			SET
				`name` = :name?s,
				allow_codes = :allowCodes?b,
				start = :start?dn,
				`end` = :end?dn,
				updated_at = :updatedAt?d,
				updated_by = :updatedBy?i
			WHERE
				bundle_id = :bundleID?i
		", [
			'name' => $bundle->getName(),
			'allowCodes' => $bundle->allowCodes(),
			'start' => $bundle->getStart(),
			'end' => $bundle->getEnd(),
			'updatedAt' => new \DateTime,
			'updatedBy' => $this->_user->id,
			'bundleID' => $bundle->getID(),
		]);

		$this->_productCreate->setTransaction($this->_transaction);
		$this->_productCreate->save($bundle);

		$this->_priceCreate->setTransaction($this->_transaction);
		$this->_priceCreate->save($bundle);

		$this->_imageCreate->setTransaction($this->_transaction);
		$this->_imageCreate->save($bundle);

		$this->_commitTransaction();
	}

	private function _commitTransaction()
	{
		if (false === $this->_transactionOverride) {
			$this->_transaction->commit();
		}
	}
}