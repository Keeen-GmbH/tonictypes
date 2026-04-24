<?php
/*
 * This file is part of the package k3n/tonictypes.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * Many thanks to Auth: B. Zagar / Maint: J. Pietschmann for sharing this extension – TYPO3 inspiring people to share!
 * Contact: support@tonictypes.com
 *
 */
namespace K3n\Tonictypes\Service\Session;

use \K3n\Tonictypes\Configuration\ExtensionConfiguration as Configuration;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class SortSessionService extends SessionService
{
	/**
	 * Session Prefix Key
	 * @var string
	 */
	const SESSION_PREFIX_KEY = "tx-tonictypes-sort";

	/**
	 * Session Keys for Sorting
	 * @var string
	 */
	const SESSION_KEY_SORT_ORDER 	= "tx-tonictypes-sort-order";
	const SESSION_KEY_SORT_BY	 	= "tx-tonictypes-sort-by";
	const SESSION_KEY_IS_SET		= "tx-tonictypes-sort-is-set";

	/**
	 * Checks if any orderings are set
	 *
	 * @return bool
	 */
	public function hasOrderings()
	{
		if ($this->getSortField() && $this->getSortOrder())
			return true;

		return false;
	}

	/**
	 * Sets the sort order
	 *
	 * @param string $order
	 * @return SortSessionService
	 */
	public function setSortOrder($order = QueryInterface::ORDER_ASCENDING)
	{
		$this->writeToSession($order, self::SESSION_KEY_SORT_ORDER);
		return $this;
	}

	/**
	 * Sets the sort by
	 *
	 * @param string $sortBy
	 * @return $this
	 */
	public function setSortField($sortBy)
	{
		$this->writeToSession($sortBy, self::SESSION_KEY_SORT_BY);
		return $this;
	}

	/**
	 * Gets the sort order
	 *
	 * @return string
	 */
	public function getSortOrder()
	{
		return $this->restoreFromSession(self::SESSION_KEY_SORT_ORDER);
	}

	/**
	 * Gets the sort by setting from session
	 *
	 * @return string
	 */
	public function getSortField()
	{
		return $this->restoreFromSession(self::SESSION_KEY_SORT_BY);
	}


}
