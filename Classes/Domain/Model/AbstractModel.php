<?php
declare(strict_types=1);
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

namespace K3n\Tonictypes\Domain\Model;

use K3n\Tonictypes\Service\FlexForm\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

abstract class AbstractModel extends AbstractEntity
{
	/**
	 * Record Creation Date
	 *
	 * @var int
	 */
	protected $crdate;

	/**
	 * Record Timestamp
	 *
	 * @var int
	 */
	protected $tstamp;

	/**
	 * Field is deleted
	 *
	 * @var boolean
	 */
	protected $deleted = false;

	/**
	 * Field is hidden
	 *
	 * @var boolean
	 */
	protected $hidden = false;

    /**
     * AbstractModel constructor
     */
    public function __construct()
    {
    }

    /**
	 * Gets the crdate
	 *
	 * @return int
	 */
	public function getCrdate(): int
	{
		return $this->crdate;
	}

	/**
	 * Sets the crdate
	 *
	 * @param int $crdate
	 * @return void
	 */
	public function setCrdate(int $crdate): void
	{
		$this->crdate = $crdate;
	}

	/**
	 * Gets the timestamp
	 *
	 * @return int
	 */
	public function getTstamp(): int
	{
		return $this->tstamp;
	}

	/**
	 * Sets the timestamp
	 *
	 * @param int $tstamp
	 * @return void
	 */
	public function setTstamp(int $tstamp): void
	{
		$this->tstamp = $tstamp;
	}

	/**
	 * Gets the deleted status
	 *
	 * @return bool
	 */
	public function isDeleted(): bool
	{
		return $this->deleted;
	}

	/**
	 * Gets the deleted status
	 *
	 * @return bool
	 */
	public function getDeleted(): bool
	{
		return $this->deleted;
	}

	/**
	 * Sets the record value deleted
	 *
	 * @param bool $deleted
	 * @return void
	 */
	public function setDeleted(bool $deleted): void
	{
		$this->deleted = $deleted;
	}

	/**
	 * Checks if the fieldvalue is hidden
	 *
	 * @return bool
	 */
	public function isHidden(): bool
	{
		return $this->hidden;
	}

	/**
	 * Checks if the fieldvalue is hidden
	 *
	 * @return bool
	 */
	public function getHidden(): bool
	{
		return $this->hidden;
	}

	/**
	 * Sets the hidden status
	 *
	 * @param bool $hidden
     * @return void
	 */
	public function setHidden(bool $hidden): void
	{
		$this->hidden = $hidden;
	}
}
