<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Query;
use Message\User\UserInterface;

/**
 * Class Delete
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class for deleting bundles. This class only soft deletes bundles.
 */
class Delete
{
	/**
	 * @var Query
	 */
	private $_query;

	/**
	 * @var UserInterface
	 */
	private $_user;

	/**
	 * @param Query $query
	 * @param UserInterface $user
	 */
	public function __construct(Query $query, UserInterface $user)
	{
		$this->_query = $query;
		$this->_user = $user;
	}

	/**
	 * Mark a bundle as deleted
	 *
	 * @param Bundle $bundle
	 */
	public function delete(Bundle $bundle)
	{
		$this->_query->run("
			UPDATE
				discount_bundle
			SET
				deleted_at = :deletedAt?d,
				deleted_by = :deletedBy?in
			WHERE
				bundle_id = :id?i
		", [
			'deletedAt' => new \DateTime,
			'deletedBy' => $this->_user->id,
			'id'        => $bundle->getID(),
		]);
	}

	/**
	 * Mark a bundle as not deleted
	 *
	 * @param Bundle $bundle
	 */
	public function restore(Bundle $bundle)
	{
		$this->_query->run("
			UPDATE
				discount_bundle
			SET
				deleted_at = NULL,
				deleted_by = NULL
			WHERE
				bundle_id = :id?i
		", [
			'id' => $bundle->getID(),
		]);
	}
}