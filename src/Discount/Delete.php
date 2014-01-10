<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\ValueObject\DateTimeImmutable;

use Message\Cog\DB;
use Message\Cog\DB\Result;
use Message\User\UserInterface;

/**
 * Decorator for deleting & restoring discounts.
 */
class Delete
{
	protected $_query;
	protected $_currentUser;

	/**
	 * Constructor.
	 *
	 * @param DB\Query            $query          The database query instance to use
	 * @param UserInterface       $currentUser    The currently logged in user
	 */
	public function __construct(DB\Query $query, UserInterface $user)
	{
		$this->_query           = $query;
		$this->_currentUser     = $user;
	}

	/**
	 * Delete a page by marking it as deleted in the database.
	 *
	 * @param  Discount   $discount The discount to be deleted
	 *
	 * @return Discount   The discount that was been deleted, with the "delete"
	 *                    authorship data set
	 */
	public function delete(Discount $discount)
	{
		$discount->authorship->delete(new DateTimeImmutable, $this->_currentUser->id);

		$result = $this->_query->run('
			UPDATE
				discount
			SET
				deleted_at = :at?i,
				deleted_by = :by?in
			WHERE
				discount_id = :id?i
		', array(
			'at' => $discount->authorship->deletedAt()->getTimestamp(),
			'by' => $discount->authorship->deletedBy(),
			'id' => $discount->id,
		));


		return $discount;
	}

	/**
	 * Restores a currently deleted discount to its former self.
	 *
	 * @param  Discount $discount	The discount to be restored
	 *
	 * @return Discount 		 	The discount, with the "delete"
	 *                    			authorship data cleared
	 */
	public function restore(Discount $discount)
	{
		$discount->authorship->restore();

		$result = $this->_query->run('
			UPDATE
				discount
			SET
				deleted_at = NULL,
				deleted_by = NULL
			WHERE
				discount_id = ?i
		', $discount->id);

		return $discount;
	}
}