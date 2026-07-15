<?php
declare(strict_types=1);
/*
 * This file is part of the package k3n/tonictypes. 
 */

namespace K3n\Tonictypes\Service\Transfer;

use K3n\Tonictypes\Configuration\ExtensionConfiguration;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\ClearCacheService;
use ZipArchive;

class DatatypeTransferImportService
{
    public const STRATEGY_CREATE = 'create';
    public const STRATEGY_UPDATE = 'update';
    public const STRATEGY_SKIP = 'skip';
    private const VARIABLE_TABLE = 'tx_tonictypes_domain_model_variable';
    private const MM_TABLE = 'tx_tonictypes_datatype_field_mm';
    private const SYS_LOG_NEWID_MAX_LENGTH_V12 = 30;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ClearCacheService $clearCacheService,
        private readonly DatatypeRecordTableMigrationService $recordTableMigrationService,
    ) {
    }

    /**
     * @return array{manifest: array<string, mixed>, datatypes: array<string, array<string, mixed>>}
     */
    public function parseArchive(string $archivePath): array
    {
        TonictypesProGuard::assertAvailable();

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('Could not open import archive.');
        }

        $manifestContent = $zip->getFromName('manifest.yaml');
        if ($manifestContent === false) {
            $zip->close();
            throw new \RuntimeException('Invalid archive: manifest.yaml is missing.');
        }

        $manifest = Yaml::parse($manifestContent);
        if (!is_array($manifest) || ($manifest['format'] ?? '') !== DatatypeTransferExportService::FORMAT) {
            $zip->close();
            throw new \RuntimeException('Invalid or unsupported transfer format.');
        }

        $datatypes = [];
        $folderToExportKey = [];

        foreach ($manifest['datatypes'] ?? [] as $manifestEntry) {
            if (!is_array($manifestEntry)) {
                continue;
            }
            $exportKey = (string)($manifestEntry['key'] ?? '');
            if ($exportKey === '') {
                continue;
            }
            $folderToExportKey[$this->sanitizeFilename($exportKey)] = $exportKey;
            $datatypes[$exportKey] = [
                'datatype' => [
                    'exportKey' => $exportKey,
                    'name' => (string)($manifestEntry['name'] ?? $exportKey),
                    'tablename' => (string)($manifestEntry['tablename'] ?? ''),
                    'sourcePid' => (int)($manifestEntry['sourcePid'] ?? 0),
                ],
                'fields' => [],
                'variables' => [],
            ];
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);
            if (!is_string($entryName)) {
                continue;
            }

            $content = $zip->getFromIndex($index);
            if ($content === false) {
                continue;
            }

            if (preg_match('#^datatypes/([^/]+)/datatype\\.yaml$#', $entryName, $matches) === 1) {
                $exportKey = $this->resolveExportKeyFromFolder($matches[1], $folderToExportKey);
                $datatypePayload = Yaml::parse($content);
                if (!is_array($datatypePayload)) {
                    continue;
                }
                $datatypes[$exportKey] = $datatypes[$exportKey] ?? [
                    'datatype' => ['exportKey' => $exportKey],
                    'fields' => [],
                    'variables' => [],
                ];
                $datatypePayload['exportKey'] = (string)($datatypePayload['exportKey'] ?? $exportKey);
                $datatypes[$exportKey]['datatype'] = $datatypePayload;
                continue;
            }

            if (preg_match('#^datatypes/([^/]+)/fields/(.+)\\.yaml$#', $entryName, $matches) === 1) {
                $exportKey = $this->resolveExportKeyFromFolder($matches[1], $folderToExportKey);
                $fieldPayload = Yaml::parse($content);
                if (!is_array($fieldPayload)) {
                    continue;
                }
                $datatypes[$exportKey] = $datatypes[$exportKey] ?? [
                    'datatype' => ['exportKey' => $exportKey],
                    'fields' => [],
                    'variables' => [],
                ];
                $fieldPayload['fieldValues'] = $fieldPayload['values'] ?? $fieldPayload['fieldValues'] ?? [];
                unset($fieldPayload['values']);
                $datatypes[$exportKey]['fields'][] = $fieldPayload;
                continue;
            }

            if (preg_match('#^datatypes/([^/]+)/variables/(.+)\\.yaml$#', $entryName, $matches) === 1) {
                $exportKey = $this->resolveExportKeyFromFolder($matches[1], $folderToExportKey);
                $variablePayload = Yaml::parse($content);
                if (!is_array($variablePayload)) {
                    continue;
                }
                $datatypes[$exportKey] = $datatypes[$exportKey] ?? [
                    'datatype' => ['exportKey' => $exportKey],
                    'fields' => [],
                    'variables' => [],
                ];
                $datatypes[$exportKey]['variables'][] = $variablePayload;
            }
        }

        foreach ($datatypes as $exportKey => $payload) {
            $fields = $payload['fields'] ?? [];
            usort(
                $fields,
                static fn(array $a, array $b): int => ((int)($a['sorting'] ?? 0)) <=> ((int)($b['sorting'] ?? 0))
            );
            $datatypes[$exportKey]['fields'] = $fields;
            $datatypes[$exportKey]['datatype']['exportKey'] = (string)($datatypes[$exportKey]['datatype']['exportKey'] ?? $exportKey);
        }

        $zip->close();

        if ($datatypes === []) {
            throw new \RuntimeException('No datatype data found in archive.');
        }

        return [
            'manifest' => $manifest,
            'datatypes' => $datatypes,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $datatypes
     * @return list<array<string, mixed>>
     */
    public function buildPreview(array $datatypes): array
    {
        TonictypesProGuard::assertAvailable();

        $preview = [];
        foreach ($datatypes as $exportKey => $payload) {
            $tablename = (string)($payload['datatype']['tablename'] ?? '');
            $name = (string)($payload['datatype']['name'] ?? $exportKey);
            $existingUid = $this->findDatatypeUidForImport($tablename, $name, 0);
            $existingPid = $existingUid > 0 ? $this->getRecordPid(ExtensionConfiguration::EXTENSION_DATATYPE_TABLE, $existingUid) : 0;
            $sourcePid = (int)($payload['datatype']['sourcePid'] ?? 0);
            $suggestedPid = $existingPid > 0 ? $existingPid : $sourcePid;
            $preview[] = [
                'exportKey' => $exportKey,
                'name' => (string)($payload['datatype']['name'] ?? $exportKey),
                'tablename' => $tablename,
                'fieldCount' => count($payload['fields'] ?? []),
                'exists' => $existingUid > 0,
                'existingUid' => $existingUid,
                'action' => $existingUid > 0 ? self::STRATEGY_UPDATE : self::STRATEGY_CREATE,
                'sourcePid' => $sourcePid,
                'sourcePageTitle' => $this->getPageTitle($sourcePid),
                'existingPid' => $existingPid,
                'existingPageTitle' => $this->getPageTitle($existingPid),
                'suggestedPid' => $suggestedPid,
            ];
        }

        return $preview;
    }

    /**
     * @param array<string, array<string, mixed>> $datatypes
     * @param array<string, int> $pidMapping exportKey => storage page uid
     * @return array{success: bool, log: list<array<string, mixed>>, imported: int, updated: int, skipped: int, errors: int}
     */
    public function importBundle(array $datatypes, array $pidMapping): array
    {
        TonictypesProGuard::assertAvailable();

        if ($pidMapping === []) {
            throw new \InvalidArgumentException('A storage page mapping is required for import.');
        }

        $strategy = self::STRATEGY_UPDATE;
        $log = [];
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($datatypes as $exportKey => $payload) {
            $datatypeImportAction = self::STRATEGY_CREATE;
            $datatypeLabel = (string)($payload['datatype']['name'] ?? $exportKey);
            $datatypeFailed = false;

            try {
                $targetPid = $this->resolveTargetPid((string)$exportKey, $pidMapping);
            } catch (\InvalidArgumentException $exception) {
                $errors++;
                $datatypeFailed = true;
                $log[] = [
                    'status' => 'error',
                    'message' => sprintf('Datatype "%s": %s', $datatypeLabel, $exception->getMessage()),
                ];
                continue;
            }

            $datatypeName = (string)($payload['datatype']['name'] ?? $exportKey);
            $datatypeTablename = (string)($payload['datatype']['tablename'] ?? '');
            $existingDatatypeUid = $this->findDatatypeUidForImport($datatypeTablename, $datatypeName, $targetPid);

            $fieldStableIdToUid = [];
            $fieldUidsInOrder = [];

            foreach ($payload['fields'] ?? [] as $fieldData) {
                if (!is_array($fieldData)) {
                    continue;
                }
                $stableId = (string)($fieldData['stableId'] ?? '');
                if ($stableId === '') {
                    continue;
                }
                try {
                    $result = $this->importField($fieldData, $targetPid, $strategy, $existingDatatypeUid);
                    $fieldStableIdToUid[$stableId] = $result['uid'];
                    $fieldUidsInOrder[] = $result['uid'];
                    if ($result['action'] === self::STRATEGY_CREATE) {
                        $imported++;
                    } elseif ($result['action'] === self::STRATEGY_UPDATE) {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $exception) {
                    $errors++;
                    $datatypeFailed = true;
                    $log[] = [
                        'status' => 'error',
                        'message' => sprintf('Datatype "%s": %s', $datatypeLabel, $exception->getMessage()),
                    ];
                    break;
                }
            }

            if ($datatypeFailed) {
                continue;
            }

            $datatypeUid = 0;
            try {
                $result = $this->importDatatype($exportKey, $payload, $targetPid, $strategy, $fieldStableIdToUid);
                $datatypeUid = $result['uid'];
                $datatypeImportAction = $result['action'];
                if ($result['action'] === self::STRATEGY_CREATE) {
                    $imported++;
                } elseif ($result['action'] === self::STRATEGY_UPDATE) {
                    $updated++;
                } else {
                    $skipped++;
                }

                if ($datatypeUid > 0) {
                    $this->syncDatatypeFieldRelations($datatypeUid, $fieldUidsInOrder);
                }
            } catch (\Throwable $exception) {
                $errors++;
                $log[] = [
                    'status' => 'error',
                    'message' => sprintf('Datatype "%s": %s', $datatypeLabel, $exception->getMessage()),
                ];
                continue;
            }

            if ($datatypeUid <= 0) {
                continue;
            }

            foreach ($payload['variables'] ?? [] as $variableData) {
                if (!is_array($variableData)) {
                    continue;
                }
                try {
                    $variableResult = $this->importVariable($variableData, $targetPid, $datatypeUid, $strategy);
                    if ($variableResult['action'] === self::STRATEGY_CREATE) {
                        $imported++;
                    } elseif ($variableResult['action'] === self::STRATEGY_UPDATE) {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $exception) {
                    $errors++;
                    $datatypeFailed = true;
                    $log[] = [
                        'status' => 'error',
                        'message' => sprintf('Datatype "%s": %s', $datatypeLabel, $exception->getMessage()),
                    ];
                    break;
                }
            }

            if ($datatypeFailed) {
                continue;
            }

            try {
                $this->recordTableMigrationService->ensureRecordTable(
                    $datatypeUid,
                    $datatypeImportAction === self::STRATEGY_CREATE
                );
            } catch (\Throwable $exception) {
                $errors++;
                $log[] = [
                    'status' => 'error',
                    'message' => sprintf(
                        'Datatype "%s": %s',
                        $datatypeLabel,
                        $this->formatRecordTableErrorMessage($exception->getMessage())
                    ),
                ];
                continue;
            }

            $log[] = [
                'status' => 'success',
                'message' => sprintf('Datatype "%s" imported.', $datatypeLabel),
            ];
        }

        $this->clearCacheService->clearAll();

        return [
            'success' => $errors === 0,
            'log' => $log,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string, mixed> $fieldData
     * @return array{uid: int, action: string, log: array<string, mixed>}
     */
    private function importField(array $fieldData, int $targetPid, string $strategy, int $datatypeUid = 0): array
    {
        $stableId = (string)($fieldData['stableId'] ?? '');
        $existingUid = $this->findFieldUidForImport($fieldData, $datatypeUid);

        if ($existingUid > 0 && $strategy === self::STRATEGY_SKIP) {
            return [
                'uid' => $existingUid,
                'action' => self::STRATEGY_SKIP,
                'log' => [
                    'status' => 'skipped',
                    'message' => sprintf('Field "%s" skipped (already exists).', $stableId),
                ],
            ];
        }

        $record = $fieldData;
        unset($record['stableId'], $record['sorting'], $record['fieldValues'], $record['field_values']);
        $record['pid'] = $targetPid;
        $record['id'] = $stableId;
        if (trim((string)($record['type'] ?? '')) === '') {
            $record['type'] = 'input';
        }
        $record['field_conf'] = $this->remapFieldConfForImport(
            (string)($record['fieldConf'] ?? $record['field_conf'] ?? ''),
            $targetPid
        );
        unset($record['fieldConf']);

        $fieldTable = ExtensionConfiguration::EXTENSION_FIELD_TABLE;
        $fieldValues = $fieldData['fieldValues'] ?? $fieldData['values'] ?? [];
        $newId = $this->generateNewRecordId('NEWfield_', $stableId);
        $dataMap = [];

        if ($existingUid > 0 && $strategy === self::STRATEGY_UPDATE) {
            $dataMap[$fieldTable][$existingUid] = $record;
            $uid = $existingUid;
            $action = self::STRATEGY_UPDATE;
        } else {
            $dataMap[$fieldTable][$newId] = $record;
            $uid = 0;
            $action = self::STRATEGY_CREATE;
        }

        if ($dataMap !== []) {
            $resolvedUid = $this->processDataMap($dataMap);
            if ($uid === 0) {
                $uid = $resolvedUid[$newId] ?? 0;
            }
        }

        if ($uid <= 0) {
            throw new \RuntimeException('Field could not be saved.');
        }

        $this->syncFieldValues($uid, $targetPid, $fieldValues, $strategy);
        $this->ensureFieldIdentity($uid, $stableId, (string)($fieldData['type'] ?? 'input'));

        return [
            'uid' => $uid,
            'action' => $action,
            'log' => [
                'status' => 'success',
                'message' => sprintf('Field "%s" %s.', $stableId, $action === self::STRATEGY_CREATE ? 'created' : 'updated'),
            ],
        ];
    }

    private function ensureFieldIdentity(int $fieldUid, string $stableId, string $type): void
    {
        $fieldTable = ExtensionConfiguration::EXTENSION_FIELD_TABLE;
        $update = [];
        if ($stableId !== '') {
            $update['id'] = $stableId;
        }
        if (trim($type) !== '') {
            $update['type'] = $type;
        }
        if ($update === []) {
            return;
        }

        $this->connectionPool->getConnectionForTable($fieldTable)->update(
            $fieldTable,
            $update,
            ['uid' => $fieldUid]
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, int> $fieldStableIdToUid
     * @return array{uid: int, action: string, log: array<string, mixed>}
     */
    private function importDatatype(
        string $exportKey,
        array $payload,
        int $targetPid,
        string $strategy,
        array $fieldStableIdToUid
    ): array {
        $datatypeData = $payload['datatype'] ?? [];
        if (!is_array($datatypeData)) {
            throw new \RuntimeException('Invalid datatype payload.');
        }

        $tablename = (string)($datatypeData['tablename'] ?? '');
        $name = (string)($datatypeData['name'] ?? $exportKey);
        $existingUid = $this->findDatatypeUidForImport($tablename, $name, $targetPid);

        if ($existingUid > 0 && $strategy === self::STRATEGY_SKIP) {
            return [
                'uid' => $existingUid,
                'action' => self::STRATEGY_SKIP,
                'log' => [
                    'status' => 'skipped',
                    'message' => sprintf('Datatype "%s" skipped (already exists).', $exportKey),
                ],
            ];
        }

        $record = $datatypeData;
        unset($record['exportKey'], $record['thumbnailFieldStableId'], $record['fields']);
        $record['pid'] = $targetPid;

        $thumbnailStableId = (string)($datatypeData['thumbnailFieldStableId'] ?? '');
        if ($thumbnailStableId !== '' && isset($fieldStableIdToUid[$thumbnailStableId])) {
            $record['thumbnail_field'] = $fieldStableIdToUid[$thumbnailStableId];
        }

        $datatypeTable = ExtensionConfiguration::EXTENSION_DATATYPE_TABLE;
        $dataMap = [];

        if ($existingUid > 0 && $strategy === self::STRATEGY_UPDATE) {
            $dataMap[$datatypeTable][$existingUid] = $record;
            $uid = $existingUid;
            $action = self::STRATEGY_UPDATE;
        } else {
            $newId = $this->generateNewRecordId('NEWdatatype_', $exportKey);
            $dataMap[$datatypeTable][$newId] = $record;
            $uid = 0;
            $action = self::STRATEGY_CREATE;
        }

        $resolved = $this->processDataMap($dataMap);
        if ($uid === 0) {
            $uid = $resolved[$newId] ?? 0;
        }

        if ($uid <= 0) {
            throw new \RuntimeException('Datatype could not be saved.');
        }

        return [
            'uid' => $uid,
            'action' => $action,
            'log' => [
                'status' => 'success',
                'message' => sprintf('Datatype "%s" %s.', $exportKey, $action === self::STRATEGY_CREATE ? 'created' : 'updated'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $variableData
     * @return array{action: string, log: array<string, mixed>}
     */
    private function importVariable(array $variableData, int $targetPid, int $datatypeUid, string $strategy): array
    {
        $variableName = (string)($variableData['variable_name'] ?? $variableData['variableName'] ?? '');
        $existingUid = $this->findVariableUid($variableName, $datatypeUid, $targetPid);

        if ($existingUid > 0 && $strategy === self::STRATEGY_SKIP) {
            return [
                'action' => self::STRATEGY_SKIP,
                'log' => [
                    'status' => 'skipped',
                    'message' => sprintf('Variable "%s" skipped.', $variableName),
                ],
            ];
        }

        $record = $variableData;
        $record['pid'] = $targetPid;
        $record['datatype'] = $datatypeUid;

        $dataMap = [];
        if ($existingUid > 0 && $strategy === self::STRATEGY_UPDATE) {
            $dataMap[self::VARIABLE_TABLE][$existingUid] = $record;
            $action = self::STRATEGY_UPDATE;
        } else {
            $dataMap[self::VARIABLE_TABLE][$this->generateNewRecordId('NEWvar_', $variableName . '_' . $datatypeUid)] = $record;
            $action = self::STRATEGY_CREATE;
        }

        $this->processDataMap($dataMap);

        return [
            'action' => $action,
            'log' => [
                'status' => 'success',
                'message' => sprintf('Variable "%s" %s.', $variableName, $action === self::STRATEGY_CREATE ? 'created' : 'updated'),
            ],
        ];
    }

    /**
     * @param array<string, array<string|int, array<string, mixed>>> $dataMap
     * @return array<string, int>
     */
    private function processDataMap(array $dataMap): array
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();

        if ($dataHandler->errorLog !== []) {
            throw new \RuntimeException(implode('; ', $dataHandler->errorLog));
        }

        $resolved = [];
        foreach ($dataHandler->substNEWwithIDs as $newId => $uid) {
            $resolved[$newId] = (int)$uid;
        }

        return $resolved;
    }

    private function findDatatypeUidByTablename(string $tablename): int
    {
        if ($tablename === '') {
            return 0;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(ExtensionConfiguration::EXTENSION_DATATYPE_TABLE);
        $uid = $queryBuilder
            ->select('uid')
            ->from(ExtensionConfiguration::EXTENSION_DATATYPE_TABLE)
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($tablename)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return (int)$uid;
    }

    private function findDatatypeUidByName(string $name, int $pid): int
    {
        if ($name === '') {
            return 0;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(ExtensionConfiguration::EXTENSION_DATATYPE_TABLE);
        $constraints = [
            $queryBuilder->expr()->eq('name', $queryBuilder->createNamedParameter($name)),
            $queryBuilder->expr()->eq('deleted', 0),
        ];
        if ($pid > 0) {
            $constraints[] = $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT));
        }

        $uid = $queryBuilder
            ->select('uid')
            ->from(ExtensionConfiguration::EXTENSION_DATATYPE_TABLE)
            ->where(...$constraints)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return (int)$uid;
    }

    private function findDatatypeUidForImport(string $tablename, string $name, int $pid): int
    {
        $uid = $this->findDatatypeUidByTablename($tablename);
        if ($uid > 0) {
            return $uid;
        }

        return $this->findDatatypeUidByName($name, $pid);
    }

    /**
     * @param array<string, mixed> $fieldData
     */
    private function findFieldUidForImport(array $fieldData, int $datatypeUid): int
    {
        if ($datatypeUid <= 0) {
            return 0;
        }

        $stableId = (string)($fieldData['stableId'] ?? '');
        if ($stableId !== '') {
            $uid = $this->findFieldUidByStableIdForDatatype($stableId, $datatypeUid);
            if ($uid > 0) {
                return $uid;
            }
        }

        $variableName = trim((string)($fieldData['variable_name'] ?? ''));
        if ($variableName !== '') {
            return $this->findFieldUidByVariableNameForDatatype($variableName, $datatypeUid);
        }

        return 0;
    }

    /**
     * @return list<int>
     */
    private function fetchFieldUidsForDatatype(int $datatypeUid): array
    {
        $fieldTable = ExtensionConfiguration::EXTENSION_FIELD_TABLE;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::MM_TABLE);
        $rows = $queryBuilder
            ->select('mm.uid_foreign', 'mm.sorting')
            ->from(self::MM_TABLE, 'mm')
            ->innerJoin(
                'mm',
                $fieldTable,
                'field',
                $queryBuilder->expr()->eq('field.uid', $queryBuilder->quoteIdentifier('mm.uid_foreign'))
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'mm.uid_local',
                    $queryBuilder->createNamedParameter($datatypeUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('field.deleted', 0)
            )
            ->orderBy('mm.sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(static fn(array $row): int => (int)$row['uid_foreign'], $rows);
    }

    private function findFieldUidByStableIdForDatatype(string $stableId, int $datatypeUid): int
    {
        if ($stableId === '' || $datatypeUid <= 0) {
            return 0;
        }

        $fieldUids = $this->fetchFieldUidsForDatatype($datatypeUid);
        if ($fieldUids === []) {
            return 0;
        }

        $fieldTable = ExtensionConfiguration::EXTENSION_FIELD_TABLE;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($fieldTable);
        $queryBuilder->getRestrictions()->removeAll();
        $uid = $queryBuilder
            ->select('uid')
            ->from($fieldTable)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($fieldUids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter($stableId)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return (int)$uid;
    }

    private function findFieldUidByVariableNameForDatatype(string $variableName, int $datatypeUid): int
    {
        if ($variableName === '' || $datatypeUid <= 0) {
            return 0;
        }

        $fieldUids = $this->fetchFieldUidsForDatatype($datatypeUid);
        if ($fieldUids === []) {
            return 0;
        }

        $fieldTable = ExtensionConfiguration::EXTENSION_FIELD_TABLE;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($fieldTable);
        $queryBuilder->getRestrictions()->removeAll();
        $uid = $queryBuilder
            ->select('uid')
            ->from($fieldTable)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($fieldUids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq('variable_name', $queryBuilder->createNamedParameter($variableName)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return (int)$uid;
    }

    /**
     * @param list<int> $fieldUids
     */
    private function syncDatatypeFieldRelations(int $datatypeUid, array $fieldUids): void
    {
        $fieldUids = array_values(array_unique(array_filter(array_map('intval', $fieldUids))));
        $connection = $this->connectionPool->getConnectionForTable(self::MM_TABLE);
        $connection->delete(self::MM_TABLE, ['uid_local' => $datatypeUid]);

        $sorting = 0;
        foreach ($fieldUids as $fieldUid) {
            if ($fieldUid <= 0) {
                continue;
            }
            $sorting++;
            $connection->insert(self::MM_TABLE, [
                'uid_local' => $datatypeUid,
                'uid_foreign' => $fieldUid,
                'sorting' => $sorting,
                'sorting_foreign' => 0,
            ]);
        }

        $datatypeTable = ExtensionConfiguration::EXTENSION_DATATYPE_TABLE;
        $connection->update(
            $datatypeTable,
            ['fields' => count($fieldUids)],
            ['uid' => $datatypeUid]
        );
    }

    /**
     * @param list<array<string, mixed>> $fieldValues
     */
    private function syncFieldValues(int $fieldUid, int $targetPid, array $fieldValues, string $strategy): void
    {
        $fieldValueTable = ExtensionConfiguration::EXTENSION_FIELD_VALUE_TABLE;
        $existingValues = $this->fetchFieldValuesByField($fieldUid);
        $usedUids = [];
        $dataMap = [];

        foreach ($fieldValues as $index => $fieldValueData) {
            if (!is_array($fieldValueData)) {
                continue;
            }

            $exportIndex = (int)($fieldValueData['exportIndex'] ?? $index);
            $exportedSorting = (int)($fieldValueData['sorting'] ?? 0);
            $sorting = $exportedSorting > 0 ? $exportedSorting : (($exportIndex + 1) * 256);
            $fieldValueData['pid'] = $targetPid;
            $fieldValueData['field'] = $fieldUid;
            $fieldValueData['sorting'] = $sorting;
            unset(
                $fieldValueData['crdate'],
                $fieldValueData['cruser_id'],
                $fieldValueData['exportIndex'],
                $fieldValueData['uid']
            );

            $existingUid = $this->matchFieldValueUid($existingValues, $sorting, $exportIndex, $usedUids);
            if ($existingUid > 0) {
                $usedUids[] = $existingUid;
                if ($strategy !== self::STRATEGY_SKIP) {
                    $dataMap[$fieldValueTable][$existingUid] = $fieldValueData;
                }
                continue;
            }

            if ($strategy === self::STRATEGY_SKIP) {
                continue;
            }

            $dataMap[$fieldValueTable][$this->generateNewRecordId('NEWfv_', $fieldUid . '_' . $index, false)] = $fieldValueData;
        }

        if ($strategy === self::STRATEGY_UPDATE) {
            $connection = $this->connectionPool->getConnectionForTable($fieldValueTable);
            foreach (array_keys($existingValues) as $existingUid) {
                if (!in_array($existingUid, $usedUids, true)) {
                    $connection->update(
                        $fieldValueTable,
                        ['deleted' => 1],
                        ['uid' => $existingUid]
                    );
                }
            }
        }

        if ($dataMap !== []) {
            $this->processDataMap($dataMap);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchFieldValuesByField(int $fieldUid): array
    {
        $fieldValueTable = ExtensionConfiguration::EXTENSION_FIELD_VALUE_TABLE;
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($fieldValueTable);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from($fieldValueTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'field',
                    $queryBuilder->createNamedParameter($fieldUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        $values = [];
        foreach ($rows as $row) {
            $values[(int)$row['uid']] = $row;
        }

        return $values;
    }

    /**
     * @param array<int, array<string, mixed>> $existingValues ordered by sorting
     * @param list<int> $usedUids
     */
    private function matchFieldValueUid(array $existingValues, int $sorting, int $exportIndex, array $usedUids): int
    {
        foreach ($existingValues as $uid => $row) {
            if (in_array($uid, $usedUids, true)) {
                continue;
            }
            if ((int)($row['sorting'] ?? 0) === $sorting) {
                return $uid;
            }
        }

        $orderedUids = array_keys($existingValues);
        if (isset($orderedUids[$exportIndex])) {
            $uid = $orderedUids[$exportIndex];
            if (!in_array($uid, $usedUids, true)) {
                return $uid;
            }
        }

        return 0;
    }

    private function findVariableUid(string $variableName, int $datatypeUid, int $pid): int
    {
        if ($variableName === '') {
            return 0;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::VARIABLE_TABLE);
        $baseConstraints = [
            $queryBuilder->expr()->eq('variable_name', $queryBuilder->createNamedParameter($variableName)),
            $queryBuilder->expr()->eq('datatype', $queryBuilder->createNamedParameter($datatypeUid, Connection::PARAM_INT)),
            $queryBuilder->expr()->eq('deleted', 0),
        ];

        $uid = $queryBuilder
            ->select('uid')
            ->from(self::VARIABLE_TABLE)
            ->where(...$baseConstraints)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
        if ((int)$uid > 0) {
            return (int)$uid;
        }

        $uid = $queryBuilder
            ->select('uid')
            ->from(self::VARIABLE_TABLE)
            ->where(
                ...array_merge($baseConstraints, [
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)),
                ])
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return (int)$uid;
    }

    /**
     * @param array<string, int> $pidMapping
     */
    private function resolveTargetPid(string $exportKey, array $pidMapping): int
    {
        $pid = (int)($pidMapping[$exportKey] ?? 0);
        if ($pid <= 0) {
            throw new \InvalidArgumentException(sprintf('No storage page mapped for datatype "%s".', $exportKey));
        }

        return $pid;
    }

    private function getRecordPid(string $tableName, int $uid): int
    {
        if ($uid <= 0) {
            return 0;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $pid = $queryBuilder
            ->select('pid')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return (int)$pid;
    }

    private function getPageTitle(int $pageUid): string
    {
        if ($pageUid <= 0) {
            return '';
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $title = $queryBuilder
            ->select('title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return is_string($title) ? $title : '';
    }

    private function formatRecordTableErrorMessage(string $message): string
    {
        if (str_contains($message, 'Data truncated for column')) {
            return $message . ' Import only adds missing columns when a record table already contains data. '
                . 'Drop the empty table and import again, or run the Tonictypes table migration manually in the backend.';
        }

        return $message;
    }

    private function remapFieldConfForImport(string $fieldConf, int $targetPid): string
    {
        if ($fieldConf === '' || !str_contains($fieldConf, '@datatype:')) {
            return $fieldConf;
        }

        return (string)preg_replace_callback(
            '/@datatype:([a-zA-Z0-9_]+)/',
            function (array $matches) use ($targetPid): string {
                $uid = $this->findDatatypeUidByTablename($matches[1]);
                return (string)($uid > 0 ? $uid : 0);
            },
            $fieldConf
        );
    }

    /**
     * @param array<string, string> $folderToExportKey
     */
    private function resolveExportKeyFromFolder(string $folderName, array $folderToExportKey): string
    {
        return $folderToExportKey[$folderName] ?? $folderName;
    }

    private function sanitizeFilename(string $exportKey): string
    {
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $exportKey) ?? $exportKey;

        return $filename !== '' ? $filename : 'datatype';
    }

    /**
     * TYPO3 v12 stores NEW ids in sys_log.NEWid (varchar(30)); longer ids fail on import.
     */
    private function generateNewRecordId(string $prefix, string $uniqueSeed, bool $hashSuffix = true): string
    {
        $suffix = $hashSuffix ? md5($uniqueSeed) : $uniqueSeed;

        if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() <= 12) {
            $maxSuffixLength = self::SYS_LOG_NEWID_MAX_LENGTH_V12 - strlen($prefix);
            if ($maxSuffixLength < 1) {
                return substr($prefix, 0, self::SYS_LOG_NEWID_MAX_LENGTH_V12);
            }

            return $prefix . substr($suffix, 0, $maxSuffixLength);
        }

        return $prefix . $suffix;
    }
}
