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

namespace K3n\Tonictypes\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class VariableRepository extends AbstractRepository
{
    /**
     * Find variables by type
     *
     * @param string $type
     * @return QueryResultInterface
     */
    public function findByType(string $type): QueryResultInterface
    {
		$query = $this->createQueryWithSettings(true,false,false);

		return $query->matching(
			$query->equals('type', $type)
		)->execute();
	}

    /**
     * Find Variables by given storage pids
     *
     * @param array $storagePids
     * @return QueryResultInterface
     */
    public function findByStoragePids(array $storagePids): QueryResultInterface
    {
		$query = $this->createQueryWithSettings(false, false, true, $storagePids);
		return $query->execute();
	}

    /**
     * Find Variables by types
     * @param array $types
     * @return QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findByTypes(array $types): QueryResultInterface
    {
        $query = $this->createQueryWithSettings(true,false,false);

        return $query->matching(
            $query->in('type', $types)
        )->execute();
    }
}
