<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 */

namespace K3n\Tonictypes\Service\Transfer;

use K3n\Tonictypes\Domain\Model\Datatype;
use K3n\Tonictypes\Domain\Repository\DatatypeRepository;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class DatatypeTransferStatusService
{
    private const MM_TABLE = 'tx_tonictypes_datatype_field_mm';

    public function __construct(
        private readonly DatatypeRepository $datatypeRepository,
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    /**
     * @return list<array{uid: int, name: string, tablename: string, fieldCount: int}>
     */
    public function getDatatypeOverview(): array
    {
        TonictypesProGuard::assertAvailable();

        $overview = [];
        foreach ($this->datatypeRepository->findAll(false, ['name' => 'ASC']) as $datatype) {
            if (!$datatype instanceof Datatype) {
                continue;
            }
            $datatypeUid = (int)$datatype->getUid();
            $overview[] = [
                'uid' => $datatypeUid,
                'name' => $datatype->getName(),
                'tablename' => $datatype->getTablename(),
                'fieldCount' => $this->countAssignedFields($datatypeUid),
            ];
        }

        return $overview;
    }

    /**
     * @return list<array{uid: int, title: string}>
     */
    public function getStoragePageOptions(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $rows = $queryBuilder
            ->selectLiteral('DISTINCT pages.uid', 'pages.title')
            ->from('pages')
            ->leftJoin(
                'pages',
                'tx_tonictypes_domain_model_datatype',
                'datatype',
                $queryBuilder->expr()->eq('datatype.pid', $queryBuilder->quoteIdentifier('pages.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq('pages.deleted', 0),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('pages.doktype', 254),
                    $queryBuilder->expr()->isNotNull('datatype.uid')
                )
            )
            ->orderBy('pages.title')
            ->executeQuery()
            ->fetchAllAssociative();

        $options = [];
        foreach ($rows as $row) {
            $uid = (int)($row['uid'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $title = trim((string)($row['title'] ?? ''));
            $options[] = ['uid' => $uid, 'title' => $title !== '' ? $title : 'PID ' . $uid];
        }

        return $options;
    }

    private function countAssignedFields(int $datatypeUid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::MM_TABLE);

        return (int)$queryBuilder
            ->count('uid_foreign')
            ->from(self::MM_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($datatypeUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
    }
}
