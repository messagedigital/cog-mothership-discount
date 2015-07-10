<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Transaction;
use Message\Cog\DB\TransactionalInterface;

class Edit implements TransactionalInterface
{
	private $_transaction;
	private $_transactionOverride = false;

	public function __construct(Transaction $transaction)
	{
		$this->_transaction = $transaction;
	}

	public function setTransaction(Transaction $transaction)
	{
		$this->_transaction = $transaction;
		$this->_transactionOverride = true;
	}

	private function _commitTransaction()
	{
		if (false === $this->_transactionOverride) {
			$this->_transaction->commit();
		}
	}
}