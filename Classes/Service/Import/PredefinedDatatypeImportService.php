<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes.
 */

namespace K3n\Tonictypes\Service\Import;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class PredefinedDatatypeImportService
{
    private const ARCHIVE_PATH = 'EXT:tonictypes/Resources/Private/Init/tonictypes-export.t3tt.zip';
    private const PREDEFINED_TABLENAME = 'tx_tonictypes_domain_model_record_tonictypes';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    public function isImportAvailable(): bool
    {
        return ExtensionManagementUtility::isLoaded('tonictypes_pro');
    }

    public function isPredefinedArchiveAlreadyImported(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_tonictypes_domain_model_datatype');

        return (bool)$queryBuilder
            ->count('uid')
            ->from('tx_tonictypes_domain_model_datatype')
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter(self::PREDEFINED_TABLENAME)),
            )
            ->executeQuery()
            ->fetchOne();
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

    /**
     * @return array{success: bool, message: string, alreadyImported?: bool, imported?: int, updated?: int, skipped?: int, errors?: int, log?: list<array<string, mixed>>}
     */
    public function importPredefinedArchive(int $storagePid): array
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser instanceof BackendUserAuthentication || !$backendUser->isAdmin()) {
            throw new \RuntimeException('Only TYPO3 administrators can import predefined datatypes.');
        }
        if ($storagePid <= 0) {
            throw new \InvalidArgumentException('Please select a storage PID.');
        }
        if (!$this->isImportAvailable()) {
            throw new \RuntimeException('Predefined datatype import requires the extension tonictypes_pro.');
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        if (!(bool)$queryBuilder->count('uid')->from('pages')->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($storagePid, Connection::PARAM_INT)),
            $queryBuilder->expr()->eq('deleted', 0)
        )->executeQuery()->fetchOne()) {
            throw new \InvalidArgumentException(sprintf('Storage PID %d does not exist.', $storagePid));
        }

        if ($this->isPredefinedArchiveAlreadyImported()) {
            return [
                'success' => false,
                'alreadyImported' => true,
                'message' => 'The predefined datatype is already present.',
            ];
        }

        $archivePath = GeneralUtility::getFileAbsFileName(self::ARCHIVE_PATH);
        if ($archivePath === '' || !is_readable($archivePath)) {
            throw new \RuntimeException('The predefined Tonictypes datatype package could not be found.');
        }

        $transferImport = GeneralUtility::makeInstance(
            \K3n\TonictypesPro\Service\Transfer\DatatypeTransferImportService::class
        );
        $bundle = $transferImport->parseArchive($archivePath);
        $pidMapping = array_fill_keys(array_map('strval', array_keys($bundle['datatypes'])), $storagePid);
        $result = $transferImport->importBundle($bundle['datatypes'], $pidMapping);

        return [
            'message' => sprintf(
                'Predefined datatype import finished: %d created, %d updated, %d error(s).',
                $result['imported'],
                $result['updated'],
                $result['errors']
            ),
        ] + $result;
    }
}
